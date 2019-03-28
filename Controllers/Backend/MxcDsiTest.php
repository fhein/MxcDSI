<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Models\Group;
use Shopware\Components\Model\ModelManager;

class Shopware_Controllers_Backend_MxcDsiTest extends BackendApplicationController
{
    protected $model = Group::class;
    protected $alias = 'innocigs_group';

    public function indexAction() {
        $this->log->enter();
        /**
         * @var ModelManager $modelManager
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
