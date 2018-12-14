<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Mapping\Filter\IgnoreFilter;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class IgnoreFilterFactory implements FactoryInterface
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
        $modelManager = $container->get('modelManager');
        $filter = new IgnoreFilter(
            $modelManager,
            $log
        );
        return $filter;
    }
}