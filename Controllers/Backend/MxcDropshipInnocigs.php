<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use Mxc\Shopware\Plugin\Database\SchemaManager;
use MxcDropshipInnocigs\Import\InnocigsUpdater;
use MxcDropshipInnocigs\Listener\InnocigsClient;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use Zend\EventManager\Event;

class Shopware_Controllers_Backend_MxcDropshipInnocigs extends BackendApplicationController
{
    protected $model = InnocigsArticle::class;
    protected $alias = 'innocigs_article';

    public function updateAction()
    {
        $this->log->enter();
        $this->log->info(get_class($this));
        try {
            // If the ArticleMapper does not exist already, it gets created via the
            // ArticleMapperFactory. This factory ties the article mapper to the
            // applications event manager. The ArticleMapper object lives in
            // the service manager only. It's operation gets triggered via
            // events only.
            $this->services->get(ArticleMapper::class);
            parent::updateAction();
            // Here all Doctrine lifecycle events are completed so we can
            // savely work with Doctrine again
            $this->services->get('events')->trigger('process_active_states', $this, []);;
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    public function importAction()
    {
        $this->log->enter();
        try {
            $sm = $this->services->get(SchemaManager::class);
            $client = $this->services->get(InnocigsClient::class);
            $sm->drop();
            $sm->create();
            $client->activate(new Event());
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    public function filterAction() {
        $this->log->enter();
        try {

        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    public function synchronizeAction() {
        $this->log->enter();
        $this->services->get(InnocigsUpdater::class);


        $this->log->leave();
    }

//    public function finalizeListQuery(QueryBuilder $builder) {
//        $builder->andWhere($builder->expr()->eq($this->alias . '.ignored', intval(false)));
//    }
}
