<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use Zend\ServiceManager\Factory\FactoryInterface;

class DropshippersCompanionFactory implements FactoryInterface
{
    use ObjectAugmentationTrait;
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
        return $this->augment($container, new DropshippersCompanion($apiClient));
    }
}