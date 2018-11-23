<?php

namespace MxcDropshipInnocigs\Plugin\Controller;

use Enlight_Controller_Request_Request;
use Enlight_Controller_Response_Response;
use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Application\LoggerInterface;
use Throwable;

class BackendApplicationController extends \Shopware_Controllers_Backend_Application
{
    /**
     * @var LoggerInterface $log
     */
    protected $log;

    /**
     * @var ContainerInterface $services
     */
    protected $services;

    public function __construct(
        Enlight_Controller_Request_Request $request,
        Enlight_Controller_Response_Response $response
    ) {
        $this->services = Application::getServices();
        $this->log = $this->services->get('logger');
        parent::__construct($request, $response);
    }

    protected function actionDo(string $action) {
        $function = $this->log->getCaller();
        $this->log->enter($function);
        try {
            $worker = 'do' . ucfirst($action);
            if (method_exists($this, $worker)) {
                $worker();
            } else {
                $action();
            }
        } catch (Throwable $e) {
            $this->log->except($e);
        } finally {
            $this->log->leave($function);
        }
    }

    protected function do() {
        $function = lcfirst(substr($this->log->getCaller(),2));
        parent::$function();
    }

    public function listAction() {
        $this->actionDo(__FUNCTION__);
    }

    public function detailAction() {
        $this->actionDo(__FUNCTION__);
    }

    public function updateAction() {
        $this->actionDo(__FUNCTION__);
    }

    public function createAction() {
        $this->actionDo(__FUNCTION__);
    }

    public function deleteAction() {
        $this->actionDo(__FUNCTION__);
    }

    public function reloadAssociationAction() {
        $this->actionDo(__FUNCTION__);
    }
}