<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Models\InnocigsGroup;

class Shopware_Controllers_Backend_InnocigsGroup extends BackendApplicationController
{
    protected $model = InnocigsGroup::class;
    protected $alias = 'innocigs_group';

    protected function getAdditionalDetailData(array $data) {
        $data['options'] = [];
        return $data;
    }

}
