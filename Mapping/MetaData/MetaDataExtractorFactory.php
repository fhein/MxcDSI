<?php

namespace MxcDropshipInnocigs\Mapping\MetaData;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Toolbox\Html\HtmlDocument;
use Zend\ServiceManager\Factory\FactoryInterface;

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