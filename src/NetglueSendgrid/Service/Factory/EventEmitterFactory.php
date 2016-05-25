<?php

namespace NetglueSendgrid\Service\Factory;

use NetglueSendgrid\Service\EventEmitter;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class EventEmitterFactory implements FactoryInterface
{
    /**
     * Return Event Emitter Service
     * @param ServiceLocatorInterface $serviceLocator
     * @return EventEmitter
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $events = $serviceLocator->get('EventManager');
        return new EventEmitter($events);
    }
}
