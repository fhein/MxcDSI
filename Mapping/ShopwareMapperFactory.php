<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareAssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareDetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareOptionMapper;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShopwareMapperFactory implements FactoryInterface
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
        $log = $container->get(Logger::class);
        $optionMapper = $container->get(ShopwareOptionMapper::class);
        $articleTool = $container->get(ArticleTool::class);
        $imageMapper = $container->get(ShopwareImageMapper::class);
        $categoryMapper = $container->get(ShopwareCategoryMapper::class);
        $detailMapper = $container->get(ShopwareDetailMapper::class);
        $associatedArticlesMapper = $container->get(ShopwareAssociatedArticlesMapper::class);
        $modelManager = $container->get('modelManager');
        $articleMapper = new ShopwareMapper(
            $modelManager,
            $articleTool,
            $optionMapper,
            $detailMapper,
            $imageMapper,
            $categoryMapper,
            $associatedArticlesMapper,
            $log
        );
        return $articleMapper;
    }
}