<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Models\Import\Model;

class Shopware_Controllers_Backend_MxcDsiImport extends BackendApplicationController
{
    protected $model = Model::class;
    protected $alias = 'import_article';

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
        $data['variants'] = [];
        return $data;
    }

    public function importAction()
    {
        $this->log->enter();
        try {
            $this->services->get(ImportClient::class)->import();
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

}
