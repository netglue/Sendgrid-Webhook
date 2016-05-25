<?php

namespace NetglueSendgrid;
use Zend\ModuleManager\Feature;

class Module implements
    Feature\ConfigProviderInterface,
    Feature\ControllerProviderInterface,
    Feature\ServiceProviderInterface
{
    /**
     * Include/Return module configuration
     * @return array
     * @implements ConfigProviderInterface
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * Return Controller Config
     * @return array
     */
    public function getControllerConfig()
    {
        return [
            'factories' => [
                'NetglueSendgrid\Mvc\Controller\WebhookController' => 'NetglueSendgrid\Mvc\Controller\Factory\WebhookControllerFactory',
            ],
        ];
    }

    /**
     * Return Service Config
     * @return array
     * @implements ServiceProviderInterface
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'NetglueSendgrid\Service\EventEmitter' => 'NetglueSendgrid\Service\Factory\EventEmitterFactory',
            ],
        ];
    }
}
