<?php

namespace MxcDropshipIntegrator\Mapping\Check;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipIntegrator\Mapping\Import\NameMapper;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

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

