<?php

namespace MxcDropshipIntegrator\Jobs;

use MxcDropshipIntegrator\Dropship\DropshipManager;
use MxcDropshipIntegrator\Mapping\ImportPriceMapper;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

/**
 * This job pulls the Inncigs purchase and recommended retail prices and updates
 * the the products and variants accordingly
 */
class UpdateInnocigsPrices
{
    public static function run()
    {
        $services = MxcDropshipIntegrator::getServices();

        $registry = MxcDropshipIntegrator::getServices()->get(DropshipManager::class);
        $client = $registry->getService(DropshipManager::SUPPLIER_INNOCIGS, 'ImportClient');
        $mapper = $services->get(ImportPriceMapper::class);
        $mapper->import($client->import(false));
    }
}