<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Listener;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MappingFilePersisterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $modelManager = $container->get('modelManager');
        $log = $container->get('logger');
        return new MappingFilePersister($modelManager, $log);
    }
}