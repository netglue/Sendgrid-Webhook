<?php

namespace NetglueSendgridTest\Mvc\Controller;

use NetglueSendgrid\Mvc\Controller\WebhookController;
use Zend\Http\Request as HttpRequest;

class WebhookControllerTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{

    public function setUp()
    {
        $this->setUseConsoleRequest(false);
        $this->setApplicationConfig(include __DIR__ . '/../../../TestConfig.php.dist');
        parent::setUp();
    }

    public function testControllerCanBeRetrievedFromServiceLocator()
    {
        $services = $this->getApplicationServiceLocator();
        $cm = $services->get('ControllerManager');
        $controller = $cm->get('NetglueSendgrid\Mvc\Controller\WebhookController');
        $this->assertInstanceOf('NetglueSendgrid\Mvc\Controller\WebhookController', $controller);
    }

    public function testControllerReturns401()
    {
        $this->dispatch('/netglue-sendgrid-events');
        $this->assertResponseStatusCode(401);
    }

    public function testGetRequestsAreInvalid()
    {
        $request = $this->getRequest();
        $request->getHeaders()->addHeaderLine('Authorization', 'Basic dGVzdDpwYXNzd29yZA==');
        $this->dispatch('/netglue-sendgrid-events');
        $this->assertResponseStatusCode(405);
    }

    public function testAnythingPostedIs200()
    {
        $request = $this->getRequest();
        $request->getHeaders()->addHeaderLine('Authorization', 'Basic dGVzdDpwYXNzd29yZA==');
        $this->dispatch('/netglue-sendgrid-events', HttpRequest::METHOD_POST);
        $this->assertResponseStatusCode(200);
    }
}
