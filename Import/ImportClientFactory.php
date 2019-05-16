<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Database\SchemaManager;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportClientFactory implements FactoryInterface
{
    use ObjectAugmentationTrait;
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
        $apiClient = $container->get(ApiClient::class);
        $schemaManager = $container->get(SchemaManager::class);
        $client = new ImportClient($schemaManager, $apiClient);
        return $this->augment($container, $client);
    }
}