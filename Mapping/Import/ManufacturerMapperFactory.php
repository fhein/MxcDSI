<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ManufacturerMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get(ProductMappings::class)->getConfig();
        return new ManufacturerMapper($config);
    }

}