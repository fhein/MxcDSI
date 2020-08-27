<?php

namespace MxcDropshipIntegrator\Mapping\Check;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcDropshipIntegrator\Mapping\Import\NameMapper;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class NameMappingConsistencyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $articleNameMapper = $container->get(NameMapper::class);
        return new NameMappingConsistency($articleNameMapper);
    }
}

