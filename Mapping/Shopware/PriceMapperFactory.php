<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\AugmentedObjectFactory;

class PriceMapperFactory extends AugmentedObjectFactory
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
        $priceEngine = $container->get(PriceEngine::class);
        return $this->augment($container, new $requestedName($priceEngine));
    }
}