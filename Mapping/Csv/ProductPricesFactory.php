<?php

namespace MxcDropshipInnocigs\Mapping\Csv;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ArticlePriceMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProductPricesFactory implements FactoryInterface
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
        $modelManager = $container->get('modelManager');
        $articleMapper = $container->get(ProductMapper::class);
        $priceMapper = $container->get(ArticlePriceMapper::class);
        $log = $container->get('logger');

        return new $requestedName($modelManager, $articleMapper, $priceMapper, $log);
    }

}