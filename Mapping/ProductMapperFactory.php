<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\ArticleCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\OptionMapper;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProductMapperFactory implements FactoryInterface
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
        $optionMapper = $container->get(OptionMapper::class);
        $articleTool = $container->get(ArticleTool::class);
        $imageMapper = $container->get(ImageMapper::class);
        $categoryMapper = $container->get(ArticleCategoryMapper::class);
        $detailMapper = $container->get(DetailMapper::class);
        $associatedArticlesMapper = $container->get(AssociatedArticlesMapper::class);
        $modelManager = $container->get('modelManager');
        $articleMapper = new ProductMapper(
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