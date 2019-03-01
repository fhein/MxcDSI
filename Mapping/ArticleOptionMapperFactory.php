<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\GroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\SetRepository;
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
        $groupRepository = $container->get(GroupRepository::class);
        $setRepository = $container->get(SetRepository::class);
        $entityValidator = $container->get(EntitiyValidator::class);
        $mapper = new ArticleOptionMapper($groupRepository, $setRepository, $entityValidator, $log);
        return $mapper;
    }
}