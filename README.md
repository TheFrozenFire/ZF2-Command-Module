Command Objects
===============

This module provides a basic implementation of the command object pattern in
ZF2, as well as aggregate command objects. Command objects are typically used
in place of stringing together the results from various unrelated service
methods in an application service, though nothing prevents you from calling
application services from command objects as well. In an architecture which
isn't service-oriented, you might instead think of command objects as an
encapsulation of the logic in your controller actions, in a portable contract.

The primary purpose of command objects is to encapsulate the dependencies and
parameters of a discrete "command" which can be invoked at the application
level.

Basic Usage
-----------

### Command Object

```php
<?php
$addToFieldOnFoo = $serviceLocator->get('Application\Command\AddToFieldOnFoo');
$addToFieldOnFoo->setFooId(1);
$addToFieldOnFoo->setFieldToAddTo('bar')
    ->setAmountToAdd(5);

$changedFoo = $addToFieldOnFoo->execute();
```

```php
<?php
namespace Foo;

use Command\AbstractCommand;
use Application\Service;

class AddToFieldOnFoo extends AbstractCommand
{
    protected $fooService;
    
    protected $fooId;
    
    protected $fieldToAddTo;
    
    protected $amountToAdd;
    
    public function __construct(Service\FooService $fooService)
    {
        $this->setFooService($fooService);
    }
    
    public function execute()
    {
        $fooService = $this->getFooService();
        
        $fooId = $this->getFooId();
        $fieldToAddTo = $this->getFieldToAddTo();
        $amountToAdd = $this->getAmountToAdd();
        
        $foo = $fooService->findById($fooId);
        
        $fieldValue = $foo->getField($fieldToAddTo);
        $fieldValue += $amountToAdd;
        
        $foo->setField($fieldToAddTo, $fieldValue);
        
        $fooService->persist($foo);
        
        return $foo;
    }
    
    public function getFooService()
    {
        return $this->fooService;
    }
    
    public function setFooService(Service\FooService $fooService)
    {
        $this->fooService = $fooService;
        return $this;
    }
    
    public function getFooId()
    {
        return $this->fooId;
    }
    
    public function setFooId($fooId)
    {
        $this->fooId = $fooId;
        return $this;
    }
    
    public function getFieldToAddTo()
    {
        return $this->fieldToAddTo;
    }
    
    public function setFieldToAddTo($fieldToAddTo)
    {
        $this->fieldToAddTo = $fieldToAddTo;
        return $this;
    }
    
    public function getAmountToAdd()
    {
        return $this->amountToAdd;
    }
    
    public function setAmountToAdd($amountToAdd)
    {
        $this->amountToAdd = $amountToAdd;
        return $this;
    }
    
}
```

### Aggregate Command Object

```php
$addToFieldOnFoo = $serviceLocator->get('Application\Command\AddToFieldOnFoo');
$addToFieldOnFoo->getFindFoo()
    ->setFooId(1);
$addToFieldOnFoo->getAddValueToFooField()
    ->setFieldToAddTo('bar')
    ->setAmountToAdd(5);

$addFieldOnFoo->getEventManager()->attach($addFieldOnFoo::guessEventName($addFieldOnFoo->getFindFoo()),
    function($event) {
        echo "Found Foo with name {$event->getResult()->getName()}".PHP_EOL;
    }, 100);

$addFieldOnFoo->getEventManager()->attach($addFieldOnFoo::guessEventName($addFieldOnFoo->getAddValueToFooField()),
    function($event) {
        $name = $event->getResult()->getName();
        $fieldToAddTo = $event->getTarget()->getFieldToAddTo();
        $newValue = $event->getResult()->getField($fieldToAddTo);
        echo "Foo with name {$name} now has '{$fieldToAddTo}' with value {$newValue}".PHP_EOL;
    }, 100);

$addFieldOnFoo->getEventManager()->attach($addFieldOnFoo::guessEventName($addFieldOnFoo->getPersistFoo()),
    function($event) {
        $name = $event->getResult()->getName();
        echo "Foo with name {$name} has been persisted".PHP_EOL;
    }, 100);

$changedFoo = $addToFieldOnFoo->execute();
```

```php
<?php
namespace Foo;

use Command\AbstractAggregateCommand;
use Command\CommandEvent;
use Application\Command;

class AddToFieldOnFoo extends AbstractAggregateCommand
{
    protected $findFoo;
    
    protected $addValueToFooField;
    
    protected $saveFoo;
    
    public function __construct(Command\FindFoo $findFoo, Command\AddValueToFooField $addValueToFooField, Command\PersistFoo $persistFoo)
    {
        $this->setFindFoo($findFoo);
        $this->setAddValueToFooField($addValueToFooField);
        $this->setPersistFoo($persistFoo);
    }
    
    public function execute()
    {
        $findFoo = $this->getFindFoo();
        $addValueToFooField = $this->getAddValueToFooField();
        $persistFoo = $this->getPersistFoo();
        
        $foo = $this->executeChild($findFoo);
        
        $addValueToFooField->setFoo($foo);
        $foo = $this->executeChild($addValueToFooField);
        
        $persistFoo->setFoo($foo);
        $foo = $this->executeChild($persistFoo);
        
        return $foo;
    }
    
    public function getFindFoo()
    {
        return $this->findFoo;
    }
    
    public function setFindFoo($findFoo)
    {
        $this->findFoo = $findFoo;
        return $this;
    }
    
    public function getAddValueToFooField()
    {
        return $this->addValueToFooField;
    }
    
    public function setAddValueToFooField($addValueToFooField)
    {
        $this->addValueToFooField = $addValueToFooField;
        return $this;
    }
    
    public function getPeristFoo()
    {
        return $this->peristFoo;
    }
    
    public function setPeristFoo($peristFoo)
    {
        $this->peristFoo = $peristFoo;
        return $this;
    }
    
}
```

### Aggregate Command Object with Event Stringing

```php
<?php
namespace Foo;

use Command\AbstractAggregateCommand;
use Command\CommandEvent;
use Application\Command;

class AddToFieldOnFoo extends AbstractAggregateCommand
{
    protected $findFoo;
    
    protected $addValueToFooField;
    
    protected $saveFoo;
    
    public function __construct(Command\FindFoo $findFoo, Command\AddValueToFooField $addValueToFooField, Command\PersistFoo $persistFoo)
    {
        $this->setFindFoo($findFoo);
        $this->setAddValueToFooField($addValueToFooField);
        $this->setPersistFoo($persistFoo);
    }
    
    public function execute()
    {
        $command = $this;
        $result = null;
    
        $findFoo = $this->getFindFoo();
        $addValueToFooField = $this->getAddValueToFooField();
        $persistFoo = $this->getPersistFoo();
        
        $command->getEventManager()->attach(static::guessEventName($findFoo), function(CommandEvent $event) use ($command, $addValueToFooField) {
            $foo = $event->getResult();
            $addValueToFooField->setFoo($foo);
            
            $command->executeChild($addValueToFooField);
        });
        
        $command->getEventManager()->attach(static::guessEventName($addValueToFooField), function(CommandEvent $event) use ($command, $persistFoo) {
            $foo = $event->getResult();
            $persistFoo->setFoo($foo);
            
            $command->executeChild($persistFoo);
        });
        
        $command->getEventManager()->attach(static::guessEventName($persistFoo), function(CommandEvent $event) use ($command, &$result) {
            $result = $event->getResult();
        });
        
        $this->executeChild($findFoo);
        
        return $foo;
    }
    
    public function getFindFoo()
    {
        return $this->findFoo;
    }
    
    public function setFindFoo($findFoo)
    {
        $this->findFoo = $findFoo;
        return $this;
    }
    
    public function getAddValueToFooField()
    {
        return $this->addValueToFooField;
    }
    
    public function setAddValueToFooField($addValueToFooField)
    {
        $this->addValueToFooField = $addValueToFooField;
        return $this;
    }
    
    public function getPersistFoo()
    {
        return $this->persistFoo;
    }
    
    public function setPersistFoo($persistFoo)
    {
        $this->persistFoo = $persistFoo;
        return $this;
    }
    
}
```

## Refactoring Controller Methods

**Without Command Object**
```php
<?php
namespace Foo;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Service;

class FooController extends AbstractActionController
{
    protected $fooService;
    
    public function __construct(Service\FooService $fooService) {
        $this->setFooService($fooService);
    }
    
    public function addValueToFieldOnFoo()
    {
        $fooService = $this->getFooService();
    
        $fooId = $this->params('foo_id');
        $fieldToAddTo = $this->params()->fromPost('field_to_add_to');
        $amountToAdd = $this->params()->fromPost('amount_to_add');
        
        $foo = $fooService->findById($fooId);
        
        $fieldValue = $foo->getField($fieldToAddTo);
        $fieldValue += $amountToAdd;
        
        $foo->setField($fieldToAddTo, $fieldValue);
        
        $fooService->persist($foo);
    }
    
    public function getFooService()
    {
        return $this->fooService;
    }
    
    public function setFooService($fooService)
    {
        $this->fooService = $fooService;
        return $this;
    }
    
}
```

**With Command Object**
```php
<?php
namespace Foo;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Command;

class FooController extends AbstractActionController
{
    protected $addToFieldOnFoo;
    
    public function __construct(Command\AddToFieldOnFoo $addToFieldOnFoo) {
        $this->setAddToFieldOnFoo($addToFieldOnFoo);
    }
    
    public function addValueToFieldOnFoo()
    {
        $addToFieldOnFoo = $this->getAddToFieldOnFoo();
    
        $fooId = $this->params('foo_id');
        $fieldToAddTo = $this->params()->fromPost('field_to_add_to');
        $amountToAdd = $this->params()->fromPost('amount_to_add');
        
        $addToFieldOnFoo->getFindFoo()->getFooId($fooId);
        $addToFieldOnFoo->getAddValueToFooField()
            ->setFieldToAddTo('bar')
            ->setAmountToAdd(5);
            
        $addToFieldOnFoo->execute();
    }
    
    public function getAddToFieldOnFoo()
    {
        return $this->addToFieldOnFoo;
    }
    
    public function setAddToFieldOnFoo($addToFieldOnFoo)
    {
        $this->addToFieldOnFoo = $addToFieldOnFoo;
        return $this;
    }
    
}
```
