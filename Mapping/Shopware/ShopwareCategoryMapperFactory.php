<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShopwareCategoryMapperFactory implements FactoryInterface
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
        $log = $container->get('logger');
        $config = $this->getClassConfig($container, $requestedName);

        $categoryTool = $container->get(CategoryTool::class);
        $mapper = new ShopwareCategoryMapper($categoryTool, $config, $log);
        return $mapper;
    }
}