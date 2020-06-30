<?php

namespace MxcDropshipInnocigs\Excel;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Mapping\Shopware\PriceEngine;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class ExportPricesFactory implements FactoryInterface
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
        $priceEngine = $container->get(PriceEngine::class);
        return $this->augment($container, new $requestedName($priceEngine, $priceMapper));
    }

}