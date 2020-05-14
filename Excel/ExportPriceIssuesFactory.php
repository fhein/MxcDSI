<?php

namespace MxcDropshipInnocigs\Excel;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

class ExportPriceIssuesFactory implements FactoryInterface
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
        return $this->augment($container, new $requestedName());
    }

}