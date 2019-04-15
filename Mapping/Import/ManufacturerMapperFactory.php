<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

class ManufacturerMapperFactory implements FactoryInterface
{
    use ClassConfigTrait;
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
        $mapping = $container->get(ImportMappings::class);
        $config = $this->getClassConfig($container, $requestedName);
        $log = $container->get('logger');

        return new ManufacturerMapper($mapping, $config, $log);
    }

}