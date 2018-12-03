<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Configurator\GroupRepository;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticleOptionMapperFactory implements FactoryInterface
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
        $repository = $container->get(GroupRepository::class);
        $pMapper = $container->get(PropertyMapper::class);
        $modelManager = $container->get('modelManager');
        $mapper = new ArticleOptionMapper($modelManager, $repository, $pMapper, $log);
        return $mapper;
    }
}