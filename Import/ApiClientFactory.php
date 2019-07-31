<?php

namespace MxcDropshipInnocigs\Import;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiClientFactory implements FactoryInterface
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
        $credentials = $container->get(Credentials::class);
        $logger = $container->get('logger');
        return new ApiClient($credentials, $logger);
    }
}