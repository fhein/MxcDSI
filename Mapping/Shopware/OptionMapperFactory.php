<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipIntegrator\Toolbox\Shopware\Configurator\GroupRepository;
use MxcDropshipIntegrator\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipIntegrator\Toolbox\Shopware\Configurator\SetRepository;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class OptionMapperFactory implements FactoryInterface
{
    use ObjectAugmentationTrait;
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
        $groupRepository = $container->get(GroupRepository::class);
        $setRepository = $container->get(SetRepository::class);
        return $this->augment($container, new OptionMapper($groupRepository, $setRepository));
    }
}