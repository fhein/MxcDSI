<?php

namespace MxcDropshipInnocigs\Plugin\Database;

use Interop\Container\ContainerInterface;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class DatabaseFactory implements FactoryInterface
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
        $entityManager = $container->get('modelManager');
        $attributeManager = $container->get('attributeManager');
        $config = $container->get('config');
        $logger = $container->get(Logger::class);
        return new Database($entityManager, $attributeManager, $config, $logger);
    }
}