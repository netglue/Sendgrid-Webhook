<?php

namespace NetglueSendgridTest\Service;

use NetglueSendgrid\Service\EventEmitter;
use Zend\EventManager\Event;
use Zend\Http\Request as HttpRequest;
use PHPUnit\Framework\TestCase;

class EventEmitterTest extends TestCase
{

    private $eventsFired = 0;

    public function setUp()
    {
        parent::setUp();
        $this->eventsFired = 0;
    }

    public function testReceiveValidRequestTriggersEvents()
    {
        $emitter = new EventEmitter();
        $request = HttpRequest::fromString(file_get_contents(__DIR__ . '/../../data/ValidSendGridRequest.txt'));
        $events = $emitter->getEventManager();
        $events->attach('*', [$this, 'checkValidEvents']);
        $this->assertSame(0, $this->eventsFired);
        $emitter->receiveRequest($request);
        $this->assertSame(11, $this->eventsFired);
    }

    public function checkValidEvents(Event $e)
    {
        $this->eventsFired++;
        $params = $e->getParams();
        $this->assertArrayHasKey('error', $params);
        $this->assertArrayHasKey('data', $params);
        $this->assertArrayHasKey('requestBody', $params);
        $this->assertArrayHasKey('event', $params['data']);

        $emitter = new EventEmitter();
        $identifiers = $emitter->getEventIdentifiers();
        $this->assertArrayHasKey($params['data']['event'], $identifiers);
    }

    public function testInvalidJsonPayload()
    {
        $emitter = new EventEmitter();
        $request = new HttpRequest;
        $request->setContent('foo');
        $events = $emitter->getEventManager();
        $events->attach('*', [$this, 'checkInvalidJson']);
        $this->eventsFired = 0;
        $emitter->receiveRequest($request);
        $this->assertSame(1, $this->eventsFired);
    }

    public function checkInvalidJson(Event $e)
    {
        $this->eventsFired++;
        $params = $e->getParams();
        $this->assertTrue($params['error']);
        $this->assertSame(EventEmitter::EVENT_UNEXPECTED_FORMAT, $e->getName());
    }

    public function testUnknownEventFormat()
    {
        $emitter = new EventEmitter();
        $request = new HttpRequest;
        $request->setContent('[{
            "email": "example@test.com",
            "timestamp": 1464173660,
            "smtp-id": "<14c5d75ce93.dfd.64b469@ismtpd-555>",
            "event": "whatever!",
            "category": "Foo",
            "sg_event_id": "lHhVQCyr17K4lAA6Xdefyg==",
            "sg_message_id": "14c5d75ce93.dfd.64b469.filter0001.16648.5515E0B88.0",
            "reason": "500 unknown recipient",
            "status": "5.0.0"
        }]');
        $events = $emitter->getEventManager();
        $events->attach('*', [$this, 'checkInvalidEvent']);
        $this->eventsFired = 0;
        $emitter->receiveRequest($request);
        $this->assertSame(1, $this->eventsFired);
    }

    public function checkInvalidEvent(Event $e)
    {
        $this->eventsFired++;
        $params = $e->getParams();
        $this->assertTrue($params['error']);
        $this->assertSame(EventEmitter::EVENT_UNEXPECTED_TYPE, $e->getName());
    }
}
