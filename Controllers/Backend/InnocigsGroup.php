<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\InnocigsClient;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsGroup;

class Shopware_Controllers_Backend_InnocigsGroup extends BackendApplicationController
{
    protected $model = InnocigsGroup::class;
    protected $alias = 'innocigs_group';

    public function indexAction() {
        $this->log->enter();
        /**
         * @var \Shopware\Components\Model\ModelManager $modelManager
         */
        try {
            $modelManager = $this->services->get('modelManager');
            $repository = $modelManager->getRepository(InnocigsArticle::class);
            $count = intval($repository->createQueryBuilder('a')->select('count(a.id)')->getQuery()->getSingleScalarResult());
            if ($count === 0) {
                $client = $this->services->get(InnocigsClient::class);
                $client->import();
            }
            parent::indexAction();
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    protected function getAdditionalDetailData(array $data) {
        $data['options'] = [];
        return $data;
    }

}
