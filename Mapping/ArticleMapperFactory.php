<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
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
        $mediaService = $container->get('mediaService');
        $articleMapper = new ArticleMapper($attributeMapper, $propertyMapper, $mediaService, $log);
        $articleMapper->attach($container->get('events'));
        return $articleMapper;
    }
}