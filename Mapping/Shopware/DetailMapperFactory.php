<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcDropshipIntegrator\Dropship\DropshipManager;
use MxcCommons\ServiceManager\Factory\FactoryInterface;


class DetailMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DropshipManager $registry */
        $registry = $container->get(DropshipManager::class);
        $articleRegistry = $registry->getService(DropshipManager::SUPPLIER_INNOCIGS, 'ArticleRegistry');
        $companion = $registry->getService(DropshipManager::SUPPLIER_INNOCIGS, 'DropshippersCompanion');
        $apiClient = $registry->getService(DropshipManager::SUPPLIER_INNOCIGS, 'ApiClient');

        $priceMapper = $container->get(PriceMapper::class);
        $articleTool = $container->get(ArticleTool::class);
        $optionMapper = $container->get(OptionMapper::class);

        return new DetailMapper(
            $articleTool,
            $apiClient,
            $companion,
            $articleRegistry,
            $priceMapper,
            $optionMapper
        );
    }
}