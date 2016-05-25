<?php

namespace NetglueSendgridTest\Service;

use NetglueSendgrid\Service\EventEmitter;
use Zend\Http\Request as HttpRequest;

class EventEmitterTest extends \Zend\Test\PHPUnit\Controller\AbstractControllerTestCase
{

    private $eventsFired = 0;

    public function setUp()
    {
        $this->setUseConsoleRequest(true);
        $this->setApplicationConfig(include __DIR__ . '/../../TestConfig.php.dist');
        parent::setUp();
    }

    public function testEmitterCanBeRetrievedFromServiceManager()
    {
        $services = $this->getApplicationServiceLocator();
        $this->assertInstanceOf('NetglueSendgrid\Service\EventEmitter', $services->get('NetglueSendgrid\Service\EventEmitter'));
    }

    public function testReceiveValidRequestTriggersEvents()
    {
        $services = $this->getApplicationServiceLocator();
        $emitter = $services->get('NetglueSendgrid\Service\EventEmitter');
        $request = HttpRequest::fromString(file_get_contents(__DIR__ . '/../../data/ValidSendGridRequest.txt'));
        $em = $emitter->getEventManager();
        $sm = $em->getSharedManager();
        $sm->attach('NetglueSendgrid\Service\EventEmitter', '*', [$this, 'checkValidEvents']);
        $this->assertSame(0, $this->eventsFired);
        $emitter->receiveRequest($request);
        $this->assertSame(11, $this->eventsFired);
    }

    public function checkValidEvents($e)
    {
        $this->eventsFired++;
        $params = $e->getParams();
        $this->assertArrayHasKey('error', $params);
        $this->assertArrayHasKey('data', $params);
        $this->assertArrayHasKey('requestBody', $params);
        $this->assertArrayHasKey('event', $params['data']);

        $services = $this->getApplicationServiceLocator();
        $emitter = $services->get('NetglueSendgrid\Service\EventEmitter');
        $identifiers = $emitter->getEventIdentifiers();
        $this->assertArrayHasKey($params['data']['event'], $identifiers);
    }

    public function testInvalidJsonPayload()
    {
        $services = $this->getApplicationServiceLocator();
        $emitter = $services->get('NetglueSendgrid\Service\EventEmitter');
        $request = new HttpRequest;
        $request->setContent('foo');
        $em = $emitter->getEventManager();
        $sm = $em->getSharedManager();
        $sm->attach('NetglueSendgrid\Service\EventEmitter', '*', [$this, 'checkInvalidJson']);
        $this->eventsFired = 0;
        $emitter->receiveRequest($request);
        $this->assertSame(1, $this->eventsFired);
    }

    public function checkInvalidJson($e)
    {
        $this->eventsFired++;
        $params = $e->getParams();
        $this->assertTrue($params['error']);
        $this->assertSame(EventEmitter::EVENT_UNEXPECTED_FORMAT, $e->getName());
    }

    public function testUnknownEventFormat()
    {
        $services = $this->getApplicationServiceLocator();
        $emitter = $services->get('NetglueSendgrid\Service\EventEmitter');
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
        $em = $emitter->getEventManager();
        $sm = $em->getSharedManager();
        $sm->attach('NetglueSendgrid\Service\EventEmitter', '*', [$this, 'checkInvalidEvent']);
        $this->eventsFired = 0;
        $emitter->receiveRequest($request);
        $this->assertSame(1, $this->eventsFired);
    }

    public function checkInvalidEvent($e)
    {
        $this->eventsFired++;
        $params = $e->getParams();
        $this->assertTrue($params['error']);
        $this->assertSame(EventEmitter::EVENT_UNEXPECTED_TYPE, $e->getName());
    }

}
