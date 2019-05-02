<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use Zend\ServiceManager\Factory\FactoryInterface;

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