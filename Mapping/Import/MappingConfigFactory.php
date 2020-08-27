<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class MappingConfigFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $productMappings = $container->get(ProductMappings::class);
        return new $requestedName($productMappings->getConfig());
    }
}