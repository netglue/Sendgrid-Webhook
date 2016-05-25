# ZF2 Module: Send Grid Events Webhook

A simple module for a ZF2 app that will receive [event webhooks from SendGrid](https://sendgrid.com/docs/API_Reference/Webhooks/event.html) and trigger a ZF2 event for each of the batched events received. You can then write your own listeners to do whatever you need to do with bounce processing etc.

## Install

Install with composer using `"netglue/sendgrid-webhook"`, enable the module in your `application.config.php` using the module name `'NetglueSendgrid'` and add custom configuration to change the route url perhaps or set up Basic HTTP Auth _(Recommended)_.

## Test

`cd` to wherever the module is installed, issue a `composer install` followed by a `phpunit`.

## Setup Basic Auth

Create a configuration file in your autoload directory named something like `sendgrid.local.php` and enter values for username and password as outlined in `./config/module.config.php`.

## Set an Alternate Webhook Endpoint/URL

Somewhere in your configuration files where you like to look after your routes, add a new one with configuration similar to this:

    'router' => [
        'routes' => [
            'sendgrid-webhook' => [
                'options' => [
                    'route' => '/somewhere/this-is-your-endpoint',
                ]
            ]
        ]
    ]

## Setup SendGrid

Navigate to your account at SendGrid and access the Settings -> Mail Settings section. Click the _Event Notification_ item and enter the url. By default, the URL should be something like: `https://yourdomain.com/netglue-sendgrid-events`.

Effectively, nothing will happen, but you should start to see something in your web server logs. You'll need to write some listeners to do something with the events triggered.

## Write A Listener

For example, in your main app's `Module.php` you could setup a listener to log the events with something like this:

    public function onBoostrap(EventInterface $e)
    {
        // Get a logger instance - change this…
        $services = $e->getApplication()->getServiceManager();
        $logger = $services->get('TheNameOfMyLogger');
        
        // Listen to all events sent by the EventEmitter
        $em = $e->getApplication()->getEventManager();
        $sharedEm = $em->getSharedManager();
        $sharedEm->attach(
            'NetglueSendgrid\Service\EventEmitter',
            '*',
            function($event) use ($logger) {
                $params = $event->getParams();
                $logger->debug(sprintf(
                    'Send Grid Event "%s" was triggered with the event name %s',
                    $params['data']['event'],
                    $event->getName()
                ));
            }
        );
    }

## Changelog

### 1.0.0
Initial release


