<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;
use MxcDropshipInnocigs\Toolbox\Shopware\PriceTool;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticleMapperFactory implements FactoryInterface
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
        $attributeMapper = $container->get(ArticleOptionMapper::class);
        $client = $container->get(ApiClient::class);
        $mediaTool = $container->get(MediaTool::class);
        $priceTool = $container->get(PriceTool::class);
        $modelManager = $container->get('modelManager');
        $articleMapper = new ArticleMapper(
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