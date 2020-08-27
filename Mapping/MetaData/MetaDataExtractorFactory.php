<?php

namespace MxcDropshipIntegrator\Mapping\MetaData;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Toolbox\Html\HtmlDocument;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class MetaDataExtractorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $htmlDocument = $container->build(HtmlDocument::class);
        return new $requestedName($htmlDocument);
    }
}