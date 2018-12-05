<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 04.12.2018
 * Time: 17:14
 */

namespace MxcDropshipInnocigs\Configurator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class SetRepositoryFactory implements FactoryInterface
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
        return new SetRepository($modelManager, $log);
    }
}


