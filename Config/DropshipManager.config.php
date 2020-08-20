<?php

namespace MxcDropshipIntegrator\Config;

use MxcDropshipIntegrator\Dropship\DropshipManager;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

return [
    'modules' => [
        DropshipManager::SUPPLIER_INNOCIGS => [
            'name'      => 'InnoCigs',
            'plugin'    => 'MxcDropshipInnocigs',
            'namespace' => 'MxcDropshipInnocigs\Services',
        ],
        DropshipManager::SUPPLIER_DEMO     => [
            'name'      => 'Demo',
            'plugin'    => 'MxcDropshipDemo',
            'namespace' => 'MxcDropshipDemo\Services',
        ],
    ],
];
