<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Toolbox\Shopware\MediaTool;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ImageMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $mediaTool = $container->get(MediaTool::class);
        return new ImageMapper($mediaTool);
    }
}