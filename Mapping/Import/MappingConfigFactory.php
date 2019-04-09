<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MappingConfigFactory implements FactoryInterface
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
        $config = $container->get(ImportMappings::class);
        $log = $container->get('logger');

        return new $requestedName($config, $log);
    }

}