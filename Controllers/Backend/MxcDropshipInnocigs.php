<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Models\InnocigsArticle;

class Shopware_Controllers_Backend_MxcDropshipInnocigs extends BackendApplicationController
{
    protected $model = InnocigsArticle::class;
    protected $alias = 'innocigs_article';

    public function updateAction()
    {
        $this->log->enter();
        try {
            // If the ArticleMapper does not exist already, it gets created via the
            // ArticleMapperFactory. This factory ties the article mapper to the
            // applications event manager. The ArticleMapper object lives in
            // the service manager only. It's operation gets triggered via
            // events only.
            $this->services->get(ArticleMapper::class);
            $this->log->info('Config path: ' . $this->getConfigPath());
            parent::updateAction();
            // Here all Doctrine lifecycle events are completed so we can
            // savely work with Doctrine again
            $this->services->get('events')->trigger('process_active_states', $this, []);;
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }
}
