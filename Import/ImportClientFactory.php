<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Database\SchemaManager;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportClientFactory implements FactoryInterface
{
    use ClassConfigTrait;
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
        $config = $this->getClassConfig($container, $requestedName);
        $apiClient = $container->get(ApiClient::class);
        $log = $container->get('logger');
        $importMapper = $container->get(ImportMapper::class);
        $modelManager = $container->get('modelManager');
        $schemaManager = $container->get(SchemaManager::class);
        $client = new ImportClient($modelManager, $schemaManager, $apiClient, $importMapper, $config, $log);
        return $client;
    }
}