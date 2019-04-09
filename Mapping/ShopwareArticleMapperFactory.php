<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareAssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwarePriceMapper;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShopwareArticleMapperFactory implements FactoryInterface
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
        $attributeMapper = $container->get(ShopwareOptionMapper::class);
        $client = $container->get(ApiClient::class);
        $imageMapper = $container->get(ShopwareImageMapper::class);
        $categoryMapper = $container->get(ShopwareCategoryMapper::class);
        $priceMapper = $container->get(ShopwarePriceMapper::class);
        $associatedArticlesMapper = $container->get(ShopwareAssociatedArticlesMapper::class);
        $modelManager = $container->get('modelManager');
        $articleMapper = new ShopwareArticleMapper(
            $modelManager,
            $attributeMapper,
            $imageMapper,
            $categoryMapper,
            $priceMapper,
            $associatedArticlesMapper,
            $client,
            $log
        );
        return $articleMapper;
    }
}