<?php

use MxcCommons\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

class Shopware_Controllers_Backend_MxcDsiImport extends BackendApplicationController
{
    protected $model = Model::class;
    protected $alias = 'mxcbc_dsi_model';

    protected function handleException(Throwable $e, bool $rethrow = false) {
        $log = MxcDropshipIntegrator::getServices()->get('logger');
        $log->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
    }
}
