<?php
declare(strict_types=1);

namespace NetglueSendgrid\Mvc\Controller\Factory;

use NetglueSendgrid\Service\EventEmitter;
use Psr\Container\ContainerInterface;
use NetglueSendgrid\Mvc\Controller\WebhookController;
use Zend\ServiceManager\AbstractPluginManager;
use NetglueSendgrid\Authentication\Adapter\Http\BasicInMemoryResolver;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;

class WebhookControllerFactory
{
    public function __invoke(ContainerInterface $container) : WebhookController
    {
        if ($container instanceof AbstractPluginManager) {
            $container = $container->getServiceLocator();
        }
        $config = $container->get('config');
        $config = isset($config['sendgrid']['webhook'])
            ? $config['sendgrid']['webhook']
            : [];
        $controller = new WebhookController(
            $container->get(EventEmitter::class)
        );
        if (isset($config['auth']['username']) && isset($config['auth']['password'])) {
            $controller->setBasicAuth($this->createBasicAuthAdapter($config['auth']));
        }
        return $controller;
    }

    private function createBasicAuthAdapter(array $config)
    {
        if (empty($config['username']) || empty($config['password'])) {
            throw new \RuntimeException('Cannot setup Basic HTTP auth without both username and password');
        }
        $realm = isset($config['realm']) ? $config['realm'] : 'SendGrid';
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
