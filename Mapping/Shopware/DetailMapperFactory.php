<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use Zend\ServiceManager\Factory\FactoryInterface;

class DetailMapperFactory implements FactoryInterface
{
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
        $log = $container->get('logger');
        $modelManager = $container->get('modelManager');
        $priceMapper = $container->get(ArticlePriceMapper::class);
        $articleTool = $container->get(ArticleTool::class);
        $companion = $container->get(DropshippersCompanion::class);
        $optionMapper = $container->get(ConfiguratorOptionMapper::class);

        return new DetailMapper($modelManager, $articleTool, $companion, $priceMapper, $optionMapper, $log);
    }
}