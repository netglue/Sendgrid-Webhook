<?php

return [

    'router' => [
        'routes' => [
            'sendgrid-webhook' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/netglue-sendgrid-events',
                    'defaults' => [
                        'controller' => 'NetglueSendgrid\Mvc\Controller\WebhookController',
                        'action' => 'event'
                    ],
                ],
            ],
        ],
    ],

    'sendgrid' => [
        'webhook' => [
            /**
             * Sendgrid Webhook Support Basic Auth
             * If you want to authenticate the remote sendgrid server,
             * Fill these in
             */
            'auth' => [
                'username' => null,
                'password' => null,
            ],
        ],
    ],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],

];
