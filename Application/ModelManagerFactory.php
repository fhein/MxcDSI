<?php

namespace MxcDropshipInnocigs\Application;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ModelManagerFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param  \Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return Shopware()->Container()->get('models');
    }
}