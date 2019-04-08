<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShopwareArticleMapperFactory implements FactoryInterface
{
    use ClassConfigTrait;
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
        $log = $container->get(Logger::class);
        $config = $this->getClassConfig($container, $requestedName);
        $attributeMapper = $container->get(ShopwareOptionMapper::class);
        $client = $container->get(ApiClient::class);
        $mediaTool = $container->get(MediaTool::class);
        $priceTool = $container->get(PriceMapper::class);
        $modelManager = $container->get('modelManager');
        $articleMapper = new ShopwareArticleMapper(
            $modelManager,
            $attributeMapper,
            $mediaTool,
            $priceTool,
            $client,
            $config,
            $log
        );
        return $articleMapper;
    }
}