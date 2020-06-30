<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\AugmentedObjectFactory;

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