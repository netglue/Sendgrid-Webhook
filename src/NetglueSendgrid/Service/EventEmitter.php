<?php

namespace NetglueSendgrid\Service;

use Zend\Http\Request as HttpRequest;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

class EventEmitter implements EventManagerAwareInterface
{

    use EventManagerAwareTrait;

    const EVENT_BOUNCE            = 'sendgrid.event.bounce';
    const EVENT_CLICK             = 'sendgrid.event.click';
    const EVENT_DEFERRED          = 'sendgrid.event.deferred';
    const EVENT_DELIVERED         = 'sendgrid.event.delivered';
    const EVENT_DROPPED           = 'sendgrid.event.dropped';
    const EVENT_OPEN              = 'sendgrid.event.open';
    const EVENT_PROCESSED         = 'sendgrid.event.processed';
    const EVENT_SPAMREPORT        = 'sendgrid.event.spamreport';
    const EVENT_UNSUBSCRIBE       = 'sendgrid.event.unsubscribe';
    const EVENT_GROUP_UNSUBSCRIBE = 'sendgrid.event.group_unsubscribe';
    const EVENT_GROUP_RESUBSCRIBE = 'sendgrid.event.group_resubscribe';

    const EVENT_UNEXPECTED_FORMAT = 'sendgrid.error.unexpected_format';
    const EVENT_UNEXPECTED_TYPE   = 'sendgrid.error.unexpected_event_type';

    private $eventIdentifier = [
        'bounce'            => self::EVENT_BOUNCE,
        'click'             => self::EVENT_CLICK,
        'deferred'          => self::EVENT_DEFERRED,
        'delivered'         => self::EVENT_DELIVERED,
        'dropped'           => self::EVENT_DROPPED,
        'open'              => self::EVENT_OPEN,
        'processed'         => self::EVENT_PROCESSED,
        'spamreport'        => self::EVENT_SPAMREPORT,
        'unsubscribe'       => self::EVENT_UNSUBSCRIBE,
        'group_unsubscribe' => self::EVENT_GROUP_UNSUBSCRIBE,
        'group_resubscribe' => self::EVENT_GROUP_RESUBSCRIBE,
    ];

    public function __construct(EventManagerInterface $events)
    {
        $this->setEventManager($events);
    }

    public function receiveRequest(HttpRequest $request)
    {
        /**
         * Iterate over all events found in the body of the request
         * and for each of these trigger an event containing the event data
         */

        $eventData = json_decode($request->getContent(), true);

        $manager = $this->getEventManager();

        $params = [
            'requestBody' => $request->getContent(),
            'error' => false,
        ];

        /**
         * Make sure that we have an array of Send Grid events
         */
        if (!is_array($eventData)) {
            $params['message'] = 'Invalid JSON Body, or unable to decode JSON payload';
            $params['error'] = true;
            $manager->trigger(self::EVENT_UNEXPECTED_FORMAT, $this, $params);
            return;
        }

        /**
         * Iterate over each Send Grid Event and trigger internal event for each
         */
        foreach ($eventData as $event) {
            $event['event'] = !isset($event['event']) ? null : $event['event'];
            $eventName = $this->resolveEventName($event['event']);
            $eventParams = $params;
            if($eventName === self::EVENT_UNEXPECTED_TYPE) {
                $eventParams['error'] = true;
                $eventParams['message'] = 'Unexpected Event Type';
            }
            $eventParams['data'] = $event;
            $manager->trigger($eventName, $this, $eventParams);
        }
    }

    private function resolveEventName($name)
    {
        $event = self::EVENT_UNEXPECTED_TYPE;
        if (is_string($name) && !empty($name)) {
            if (isset($this->eventIdentifier[$name])) {
                $event = $this->eventIdentifier[$name];
            }
        }
        return $event;
    }

}
