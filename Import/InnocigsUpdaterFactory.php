<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class InnocigsUpdaterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $modelManager = $container->get('modelManager');
        $log = $container->get('logger');
        return new InnocigsUpdater($modelManager, $log);
    }
}