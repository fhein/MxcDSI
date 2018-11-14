<?php

use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Mapping\ArticleMapper;

class Shopware_Controllers_Backend_MxcDropshipInnocigs extends \Shopware_Controllers_Backend_Application
{
    protected $model = InnocigsArticle::class;
    protected $alias = 'innocigs_article';

    protected $services;
    protected $log;

    public function __construct(
        Enlight_Controller_Request_Request $request,
        Enlight_Controller_Response_Response $response
    ) {
        $this->services = Application::getServices();
        $this->log = $this->services->get('logger');
        parent::__construct($request, $response);
    }

    public function updateAction()
    {
        // If the ArticleMapper does not exist already, it gets created via the
        // ArticleMapperFactory. This factory ties the article mapper to the
        // applications event manager. The ArticleMapper object lives in
        // the service manager only. It's operation gets triggered via
        // events only.
        try {
            $this->services->get(ArticleMapper::class);
            parent::updateAction();
            // Here all Doctrine lifecycle events are completed so we can
            // savely work with Doctrine again
            $this->services->get('events')->trigger('process_active_states', $this, []);;
        } catch (Exception $e) {
            $this->services->get('exceptionLogger')->log($e);
        }
    }
}
