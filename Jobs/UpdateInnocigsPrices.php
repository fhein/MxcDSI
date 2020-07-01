<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Mapping\ImportPriceMapper;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

/**
 * This job pulls the Inncigs purchase and recommended retail prices and updates
 * the the products and variants accordingly
 */
class UpdateInnocigsPrices
{
    public static function run()
    {
        $services = MxcDropshipInnocigs::getServices();
        $client = $services->get(ImportClient::class);
        $mapper = $services->get(ImportPriceMapper::class);
        $mapper->import($client->import(false));
    }
}