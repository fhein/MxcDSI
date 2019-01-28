<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Models\Group;

class Shopware_Controllers_Backend_MxcDsiGroup extends BackendApplicationController
{
    protected $model = Group::class;
    protected $alias = 'innocigs_group';

    public function indexAction() {
        $this->log->enter();
        /**
         * @var \Shopware\Components\Model\ModelManager $modelManager
         */
        try {
            $this->services->get(ImportClient::class)->import();
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
