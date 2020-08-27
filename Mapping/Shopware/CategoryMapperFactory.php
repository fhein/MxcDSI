<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Toolbox\Shopware\CategoryTool;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class CategoryMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $categoryTool = $container->get(CategoryTool::class);
        return new CategoryMapper($categoryTool);
    }
}