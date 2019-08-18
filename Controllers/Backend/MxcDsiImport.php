<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class Shopware_Controllers_Backend_MxcDsiImport extends BackendApplicationController
{
    protected $model = Model::class;
    protected $alias = 'record';

    protected function handleException(Throwable $e, bool $rethrow = false) {
        $log = MxcDropshipInnocigs::getServices()->get('logger');
        $log->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
    }
}
