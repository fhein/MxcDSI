<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcDropship\Dropship\DropshipManager;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropship\MxcDropship;
use Shopware\Components\Api\Resource\Article;


class DetailMapperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DropshipManager $dropshipManager */
        $dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);
        $supplier = 'InnoCigs';

        $articleRegistry = $dropshipManager->getService($supplier, 'ArticleRegistry');
        $apiClient = $dropshipManager->getService($supplier, 'ApiClient');

        $priceMapper = $container->get(PriceMapper::class);
        $articleTool = $container->get(ArticleTool::class);
        $optionMapper = $container->get(OptionMapper::class);
        $articleResource = new Article();
        $articleResource->setManager($container->get('models'));

        return new DetailMapper(
            $articleTool,
            $articleResource,
            $apiClient,
            $articleRegistry,
            $priceMapper,
            $optionMapper
        );
    }
}