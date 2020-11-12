<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Toolbox\Shopware\CategoryTool;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class BulkPriceMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $priceEngine = $container->get(PriceEngine::class);
        $priceMapper = $container->get(PriceMapper::class);
        return new BulkPriceMapper($priceEngine, $priceMapper);
    }
}