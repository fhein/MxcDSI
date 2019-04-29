<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

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