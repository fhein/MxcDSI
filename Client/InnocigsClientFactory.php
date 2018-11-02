<?php

namespace MxcDropshipInnocigs\Client;

use Interop\Container\ContainerInterface;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class InnocigsClientFactory implements FactoryInterface
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
        $apiClient = $container->get(ApiClient::class);
        $modelManager = $container->get('modelManager');
        $logger = $container->get(Logger::class);
        return new InnocigsClient($modelManager, $apiClient, $logger);
    }
}