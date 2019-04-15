<?php

namespace MxcDropshipInnocigs\Mapping\Check;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\Import\NameMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class NameMappingConsistencyFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $modelManager = $container->get('modelManager');
        $articleNameMapper = $container->get(NameMapper::class);
        $log = $container->get('logger');

        return new NameMappingConsistency($modelManager, $articleNameMapper, $log);
    }
}

