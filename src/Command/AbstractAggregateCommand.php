<?php
namespace Command;

abstract class AbstractAggregateCommand extends AbstractCommand
{
    protected function executeChild(CommandInterface $command, $name = null)
    {
        $name = $name?:static::guessEventName($child);
        
        $event = new CommandEvent($name, $command);
        
        $this->getEventManager()->attach($name, function(CommandEvent $event) {
            $result = $event->getTarget()->execute();
            $event->setResult($result);
        });
        
        $this->getEventManager()->trigger($event);
        
        return $event->getResult();
    }
    
    public static function guessEventName(CommandInterface $command, $separator = '-') {
        $baseName = implode('', array_slice(explode('\\', get_class($command)), -1));
        $guessedName = preg_replace_callback('/([A-Z])/', function($letters) use ($separator) {
            $letter = array_shift($letters);
            
            return $separator.strtolower($letter);
        }, $baseName);
        
        return $guessedName;
    }
}
