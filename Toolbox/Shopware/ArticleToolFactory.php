<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareOptionMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticleToolFactory implements FactoryInterface
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
        $optionMapper = $container->get(ShopwareOptionMapper::class);
        return new ArticleTool($modelManager, $optionMapper, $log);
    }
}


