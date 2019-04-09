<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShopwareImageMapperFactory implements FactoryInterface
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
        $log = $container->get('logger');
        $mediaTool = $container->get(MediaTool::class);

        $mapper = new ShopwareImageMapper($mediaTool, $log);
        return $mapper;
    }
}