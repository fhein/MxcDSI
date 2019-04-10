<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\GroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\SetRepository;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShopwareOptionMapperFactory implements FactoryInterface
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
        $modelManager = $container->get('modelManager');
        $groupRepository = $container->get(GroupRepository::class);
        $setRepository = $container->get(SetRepository::class);
        $mapper = new ShopwareOptionMapper($groupRepository, $setRepository, $modelManager, $log);
        return $mapper;
    }
}