<?php
namespace ZabbixReporting\Command;

use Zend\Stdlib\Hydrator;
use Zend\EventManager;

abstract class AbstractCommand implements CommandInterface
{
    use Hydrator\HydratorAwareTrait {
        Hydrator\HydratorAwareTrait::getHydrator as private traitGetHydrator;
    };
    use EventManager\EventManagerAwareTrait;
    
    public function getHydrator()
    {
        if(!$this->hydrator) {
            $hydrator = new Hydrator\ClassMethods;
            self::addHydratorMethodFilter($hydrator, 'getHydrator');
            self::addHydratorMethodFilter($hydrator, 'getEventManager');
            
            $this->setHydrator($hydrator);
        }
        
        return $this->traitGetHydrator();
    }
    
    private static function addHydratorMethodFilter($hydrator, $method)
    {
        $hydrator->addFilter($method, new Filter\MethodMatchFilter($method), Filter\FilterComposite::CONDITION_AND);
    }
}
