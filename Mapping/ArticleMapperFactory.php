<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Client\InnocigsClient;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticleMapperFactory implements FactoryInterface
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
        $log = $container->get(Logger::class);
        $attributeMapper = $container->get(ArticleOptionMapper::class);
        $propertyMapper = $container->get(PropertyMapper::class);
        $client = $container->get(InnocigsClient::class);
        $mediaService = $container->get('mediaService');
        $articleMapper = new ArticleMapper($attributeMapper, $propertyMapper, $mediaService, $client, $log);
        $articleMapper->attach($container->get('events'));
        return $articleMapper;
    }
}