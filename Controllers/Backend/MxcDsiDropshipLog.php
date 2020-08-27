<?php

use MxcCommons\Plugin\Controller\BackendApplicationController;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcDropshipIntegrator\Models\DropshipLogEntry;

class Shopware_Controllers_Backend_MxcDsiDropshipLog extends BackendApplicationController
{
    protected $model = DropshipLogEntry::class;
    protected $alias = 'dsilog';

    protected function handleException(Throwable $e, bool $rethrow = false) {
        $log = MxcDropshipIntegrator::getServices()->get('logger');
        $log->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
    }
}
