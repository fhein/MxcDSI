<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\InnocigsClient;
use MxcDropshipInnocigs\Models\InnocigsGroup;

class Shopware_Controllers_Backend_MxcDsiGroup extends BackendApplicationController
{
    protected $model = InnocigsGroup::class;
    protected $alias = 'innocigs_group';

    public function indexAction() {
        $this->log->enter();
        /**
         * @var \Shopware\Components\Model\ModelManager $modelManager
         */
        try {
            $this->services->get(InnocigsClient::class)->import();
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
