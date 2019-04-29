<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Models\Model;

class Shopware_Controllers_Backend_MxcDsiImport extends BackendApplicationController
{
    protected $model = Model::class;
    protected $alias = 'record';
}
