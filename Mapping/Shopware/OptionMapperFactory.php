<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Toolbox\Shopware\Configurator\GroupRepository;
use MxcCommons\Toolbox\Shopware\Configurator\SetRepository;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class OptionMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $groupRepository = $container->get(GroupRepository::class);
        $setRepository = $container->get(SetRepository::class);
        return new OptionMapper($groupRepository, $setRepository);
    }
}