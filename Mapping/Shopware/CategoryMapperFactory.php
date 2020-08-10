<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipIntegrator\Toolbox\Shopware\CategoryTool;
use MxcDropshipIntegrator\Toolbox\Shopware\Configurator\OptionSorter;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class CategoryMapperFactory implements FactoryInterface
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
        $categoryTool = $container->get(CategoryTool::class);
        $mapper = new CategoryMapper($categoryTool);
        return $this->augment($container, $mapper);
    }
}