<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Listener;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

class MappingFilePersisterFactory implements FactoryInterface
{
    use ClassConfigTrait;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $config = $this->getClassConfig($container, $requestedName);
        $modelManager = $container->get('modelManager');
        $log = $container->get('logger');
        return new MappingFilePersister($modelManager, $config, $log);
    }
}