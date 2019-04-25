<?php

namespace MxcDropshipInnocigs\Mapping\Check;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Mapping\Import\NameMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class NameMappingConsistencyFactory implements FactoryInterface
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
        $articleNameMapper = $container->get(NameMapper::class);
        return $this->augment($container, new NameMappingConsistency($articleNameMapper));
    }
}

