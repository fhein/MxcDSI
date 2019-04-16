<?php

namespace MxcDropshipInnocigs\Excel;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class ExportPricesFactory implements FactoryInterface
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
        $priceMapper = $container->get(PriceMapper::class);
        $log = $container->get('logger');

        return new $requestedName($modelManager, $priceMapper, $log);
    }

}