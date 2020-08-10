<?php

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ImportPricesFactory implements FactoryInterface
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
        $priceMapper = $container->get(PriceMapper::class);
        return $this->augment($container, new $requestedName($priceMapper));
    }
}