<?php

namespace MxcDropshipInnocigs\Mapping\Csv;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticlePricesFactory implements FactoryInterface
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
        $log = $container->get('logger');

        return new $requestedName($modelManager, $log);
    }

}