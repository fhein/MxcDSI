<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Listener;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticleAttributeFilePersisterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $config = $container->get('config')->plugin->$requestedName;
        $modelManager = $container->get('modelManager');
        $log = $container->get('logger');
        return new ArticleAttributeFilePersister($modelManager, $config, $log);
    }
}