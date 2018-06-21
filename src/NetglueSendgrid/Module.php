<?php
declare(strict_types=1);

namespace NetglueSendgrid;

use Zend\ModuleManager\Feature;
use Zend\ServiceManager\Factory\InvokableFactory;

class Module implements
    Feature\ConfigProviderInterface,
    Feature\ControllerProviderInterface,
    Feature\ServiceProviderInterface
{

    public function getConfig() : array
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getControllerConfig() : array
    {
        return [
            'factories' => [
                Mvc\Controller\WebhookController::class => Mvc\Controller\Factory\WebhookControllerFactory::class,
            ],
        ];
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                Service\EventEmitter::class => InvokableFactory::class,
            ],
        ];
    }
}
