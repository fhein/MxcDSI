<?php

namespace MxcDropshipIntegrator\Mapping\MetaData;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipIntegrator\Toolbox\Html\HtmlDocument;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class MetaDataExtractorFactory implements FactoryInterface
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
        $htmlDocument = $container->build(HtmlDocument::class);
        return $this->augment($container, new $requestedName($htmlDocument));
    }
}