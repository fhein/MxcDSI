<?php

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ImportPricesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $priceMapper = $container->get(PriceMapper::class);
        return new $requestedName($priceMapper);
    }
}