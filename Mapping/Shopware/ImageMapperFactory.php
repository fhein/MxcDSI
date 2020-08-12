<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcCommons\Toolbox\Shopware\MediaTool;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ImageMapperFactory implements FactoryInterface
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
        $mediaTool = $container->get(MediaTool::class);
        return $this->augment($container, new ImageMapper($mediaTool));
    }
}