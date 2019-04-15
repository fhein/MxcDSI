<?php

namespace MxcDropshipInnocigs\Mapping\Gui;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProductUpdaterFactory implements FactoryInterface
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
        $productMapper = $container->get(ProductMapper::class);
        $modelManager = $container->get('modelManager');

        return new ProductUpdater($modelManager, $productMapper, $log);
    }
}