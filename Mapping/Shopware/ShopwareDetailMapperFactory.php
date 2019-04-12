<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShopwareDetailMapperFactory implements FactoryInterface
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
        $log = $container->get('logger');
        $modelManager = $container->get('modelManager');
        $priceMapper = $container->get(ShopwarePriceMapper::class);
        $apiClient = $container->get(ApiClient::class);

        return new ShopwareDetailMapper($modelManager, $priceMapper, $apiClient, $log);
    }
}