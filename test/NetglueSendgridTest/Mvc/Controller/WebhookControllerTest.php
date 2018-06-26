<?php
declare(strict_types=1);

namespace NetglueSendgridTest\Mvc\Controller;

use NetglueSendgrid\Authentication\Adapter\Http\BasicInMemoryResolver;
use NetglueSendgrid\Mvc\Controller\WebhookController;
use NetglueSendgrid\Mvc\Controller\Factory\WebhookControllerFactory;
use NetglueSendgrid\Service\EventEmitter;
use Psr\Container\ContainerInterface;
use Zend\Http\Request;
use Zend\Http\Response;
use PHPUnit\Framework\TestCase;
use Zend\Router\RouteMatch;
use Zend\View\Model\JsonModel;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;

class WebhookControllerTest extends TestCase
{

    private $emitter;

    /** @var WebhookController */
    private $controller;

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    public function setUp()
    {
        parent::setUp();
        $this->emitter = new EventEmitter();
        $this->controller = new WebhookController($this->emitter);
        $this->response = new Response();
        $this->request  = new Request();
    }

    private function dispatchAction(string $action = 'event')
    {
        $event = $this->controller->getEvent();
        $event->setRouteMatch(new RouteMatch(['action' => $action]));
        return $this->controller->dispatch($this->request, $this->response);
    }


    public function testControllerOnlyAcceptsPostRequests()
    {
        /** @var JsonModel $model */
        $model = $this->dispatchAction();
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame('Method Not Allowed', $model->getVariable('error')['message']);
        $this->assertSame(405, $this->response->getStatusCode());
    }

    private function prepareBasicAuth()
    {
        $options = [
            'realm' => 'Auth',
            'accept_schemes' => 'basic',
        ];
        $adapter = new BasicHttpAuth($options);
        $adapter->setBasicResolver(new BasicInMemoryResolver('username', 'password'));
        $this->controller->setBasicAuth($adapter);
    }

    public function testIs401WhenAuthFails()
    {
        $this->prepareBasicAuth();
        /** @var JsonModel $model */
        $model = $this->dispatchAction();
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame('Authentication Failed', $model->getVariable('error')['message']);
        $this->assertSame(401, $this->response->getStatusCode());
    }

    public function testIs200WhenAuthSucceeds()
    {
        $this->request->setMethod('POST');
        $this->prepareBasicAuth();
        $headers = $this->request->getHeaders();
        $headers->addHeaderLine(sprintf('Authorization: Basic %s', \base64_encode('username:password')));
        /** @var JsonModel $model */
        $model = $this->dispatchAction();
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame(200, $this->response->getStatusCode());
    }

    public function testPostIsSuccessWithoutAuth()
    {
        $this->request->setMethod('POST');
        /** @var JsonModel $model */
        $model = $this->dispatchAction();
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame(200, $this->response->getStatusCode());
    }

    public function testNonHttpRequestIsError()
    {
        $this->request = new \Zend\Stdlib\Request();
        /** @var JsonModel $model */
        $model = $this->dispatchAction();
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertSame(400, $this->response->getStatusCode());
        $this->assertContains('Invalid request or response object', $model->getVariable('error')['message']);
    }

    public function testNonHttpResponseIsError()
    {
        $this->response = new \Zend\Stdlib\Response();
        /** @var JsonModel $model */
        $model = $this->dispatchAction();
        $this->assertInstanceOf(JsonModel::class, $model);
        $this->assertContains('Invalid request or response object', $model->getVariable('error')['message']);
    }

    public function testFactory()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([]);
        $container->get(EventEmitter::class)->willReturn($this->emitter);
        $controller = (new WebhookControllerFactory)($container->reveal());
        $this->assertInstanceOf(WebhookController::class, $controller);
    }

    public function testFactorySuccessfullyConfiguresAuth()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'sendgrid' => [
                'webhook' => [
                    'auth' => [
                        'username' => 'me',
                        'password' => 'foo',
                    ],
                ],
            ],
        ]);
        $container->get(EventEmitter::class)->willReturn($this->emitter);
        $this->controller = (new WebhookControllerFactory)($container->reveal());
        $model = $this->dispatchAction();
        $this->assertSame(401, $this->response->getStatusCode());
    }

}
