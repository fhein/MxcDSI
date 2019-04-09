<?php

namespace MxcDropshipInnocigs\Import\Report;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class PropertyMapperFactory implements FactoryInterface
{
    /** @var LoggerInterface $log */
    protected $log;

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

        return new PropertyMapper($log);
    }
}

