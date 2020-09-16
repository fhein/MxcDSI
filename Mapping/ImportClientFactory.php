<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipIntegrator\Mapping;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Database\SchemaManager;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class ImportClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DropshipManager $dropshipManager */
        $dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);
        $supplierId = $dropshipManager->getSupplierIdByName('InnoCigs');

        $apiClient = $dropshipManager->getService($supplierId, 'ApiClient');
        $schemaManager = $container->get(SchemaManager::class);
        return new ImportClient($schemaManager, $apiClient);
    }
}