<?php
namespace ZabbixReporting\Command;

use Zend\EventManager;

class CommandEvent extends EventManager\Event
{
    protected $result
    
    public function getResult()
    {
        return $this->result;
    }
    
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }
}
