<?php

namespace MxcDropshipInnocigs\Mapping\Gui;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProductUpdaterFactory implements FactoryInterface
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
        $productMapper = $container->get(ProductMapper::class);
        return $this->augment($container, new ProductUpdater($productMapper));
    }
}