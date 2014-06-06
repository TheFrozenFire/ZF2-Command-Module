<?php
namespace Command;

use Zend\Stdlib\Hydrator;
use Zend\EventManager;

interface CommandInterface extends Hydrator\HydratorAwareInterface, EventManager\EventManagerAwareInterface
{
    public function execute();
}
