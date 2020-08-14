<?php

namespace MxcDropshipIntegrator\Config;

use MxcDropshipIntegrator\Dropship\SupplierRegistry;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

return [
    SupplierRegistry::SUPPLIER_INNOCIGS => [
        'name'      => 'InnoCigs',
        'module'  => MxcDropshipInnocigs::class,
        'namespace' => 'MxcDropshipInnocigs\Services',
    ],
    SupplierRegistry::SUPPLIER_DEMO     => [
        'name'      => 'Demo',
        'module'  => null,
        'namespace' => 'MxcDropshipInnocigs\Services',
    ],
];
