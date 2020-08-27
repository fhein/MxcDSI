<?php

namespace MxcDropshipIntegrator\Mapping;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcDropshipIntegrator\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipIntegrator\Mapping\Shopware\CategoryMapper;
use MxcDropshipIntegrator\Mapping\Shopware\DetailMapper;
use MxcDropshipIntegrator\Mapping\Shopware\ImageMapper;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ProductMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $articleTool = $container->get(ArticleTool::class);
        $imageMapper = $container->get(ImageMapper::class);
        $categoryMapper = $container->get(CategoryMapper::class);
        $detailMapper = $container->get(DetailMapper::class);
        $associatedArticlesMapper = $container->get(AssociatedArticlesMapper::class);
        return new ProductMapper(
            $articleTool,
            $detailMapper,
            $imageMapper,
            $categoryMapper,
            $associatedArticlesMapper
        );
    }
}