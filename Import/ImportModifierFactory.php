<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Zend\Config\Config;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportModifierFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $modelManager = $container->get('modelManager');
        $log = $container->get('logger');
        $config = $container->get('config');
        $config = $config->import->update ?? new Config([]);
        $updater = new BulkOperation($modelManager, $log);
        return new ImportModifier($updater, $config);
    }
}