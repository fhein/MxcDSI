<?php

namespace MxcDropshipInnocigs\Listener;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Filter\GroupRepository;
use Zend\ServiceManager\Factory\FactoryInterface;

class FilterTestFactory implements FactoryInterface
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
        $options = $container->get('config')->plugin->$requestedName;
        $repository = $container->get(GroupRepository::class);
        $log = $container->get('logger');
        return new FilterTest($repository, $options, $log);
    }
}