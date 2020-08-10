<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class MappingConfigFactory implements FactoryInterface
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
        $productMappings = $container->get(ProductMappings::class);
        return $this->augment($container, new $requestedName($productMappings->getConfig()));
    }
}