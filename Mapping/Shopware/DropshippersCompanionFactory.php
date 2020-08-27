<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Services\ApiClient;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class DropshippersCompanionFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $apiClient = $container->get(ApiClient::class);
        return new DropshippersCompanion($apiClient);
    }
}