<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\MediaTool;
use Zend\ServiceManager\Factory\FactoryInterface;

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