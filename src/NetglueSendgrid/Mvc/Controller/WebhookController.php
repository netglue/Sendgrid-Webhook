<?php

namespace NetglueSendgrid\Mvc\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Application;
use Psr\Log;
use Zend\Authentication\Adapter\Http as BasicHttpAuth;
use NetglueSendgrid\Service\EventEmitter;

class WebhookController extends AbstractActionController
{

    use Log\LoggerAwareTrait;

    /**
     * @var BasicHttpAuth|null
     */
    private $auth = null;

    /**
     * @var EventEmitter
     */
    private $emitter;

    public function __construct(EventEmitter $service)
    {
        $this->emitter = $service;
    }

    public function eventAction()
    {
        /**
         * If Basic Auth is configured, authenticate the request
         */
        if ($this->auth) {
            $this->auth->setRequest($this->getRequest());
            $this->auth->setResponse($this->getResponse());
            $result = $this->auth->authenticate();
            if (!$result->isValid()) {
                return $this->appError('Authentication Failed', $this->getResponse()->getStatusCode(), 'auth_error');
            }
        }

        /**
         * All SendGrid Requests are POSTed
         */
        if (!$this->getRequest()->isPost()) {
            return $this->appError('Method Not Allowed', 405, 'general_error');
        }

        /**
         * Trigger Events for Listeners
         */
        $this->emitter->receiveRequest($this->getRequest());

        /**
         * Return an Empty 200 Response
         */
        return $this->getResponse();
    }

    /**
     * Set (Ready Configured) Basic Auth Adapter
     * @param BasicHttpAuth $adapter
     * @return null
     */
    public function setBasicAuth(BasicHttpAuth $adapter)
    {
        $this->auth = $adapter;
    }

    /**
     * Raise a generic app error
     * @param string $message
     * @param int $code
     * @return JsonModel
     */
    private function appError($message, $code = 400, $type = 'general_error')
    {
        $e = $this->getEvent();
        $response = $e->getResponse();
        if ($response instanceof HttpResponse) {
            $response->setStatusCode($code);
        }
        $view = new JsonModel;
        $view->error = array(
            'type' => $type,
            'message' => $message,
            'code' => $code
        );
        $this->log('error', sprintf('API Error: %s', $message), [$view->error]);
        return $view;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    private function log($level, $message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

}
