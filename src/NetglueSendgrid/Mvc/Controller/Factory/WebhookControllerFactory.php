<?php

namespace NetglueSendgrid\Mvc\Controller\Factory;

use NetglueSendgrid\Mvc\Controller\WebhookController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use NetglueSendgrid\Authentication\Adapter\Http\BasicInMemoryResolver;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;

class WebhookControllerFactory implements FactoryInterface
{
    /**
     * Return Webhook Controller
     * @param  ServiceLocatorInterface $controllerManager
     * @return WebhookController
     */
    public function createService(ServiceLocatorInterface $controllerManager)
    {
        $serviceLocator = $controllerManager->getServiceLocator();

        $config = $serviceLocator->get('Config');
        $config = isset($config['sendgrid']['webhook']) ? $config['sendgrid']['webhook'] : [];

        $emitter = $serviceLocator->get('NetglueSendgrid\Service\EventEmitter');
        $controller = new WebhookController($emitter);

        if(isset($config['auth']['username']) && isset($config['auth']['password'])) {
            $controller->setBasicAuth($this->createBasicAuthAdapter($config['auth']));
        }
        return $controller;
    }

    private function createBasicAuthAdapter(array $config)
    {
        if(empty($config['username']) || empty($config['password'])) {
            throw new \RuntimeException('Cannot setup Basic HTTP auth without both username and password');
        }
        $realm = isset($config['realm']) ? $config['realm'] : 'Password Required';
        $resolver = new BasicInMemoryResolver($config['username'], $config['password']);
        $options = [
            'realm' => $realm,
            'accept_schemes' => 'basic',
        ];
        $adapter = new BasicHttpAuth($options);
        $adapter->setBasicResolver($resolver);

        return $adapter;
    }

}
