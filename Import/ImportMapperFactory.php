<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Mapping\ImportPropertyMapper;
use MxcDropshipInnocigs\Mapping\ShopwareArticleMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportMapperFactory implements FactoryInterface
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
        $modelManager = $container->get('modelManager');
        $propertyMapper = $container->get(ImportPropertyMapper::class);
        $articleMapper = $container->get(ShopwareArticleMapper::class);
        $bulkOperation = new BulkOperation($container->get('modelManager'), $log);
        return new ImportMapper(
            $modelManager,
            $apiClient,
            $propertyMapper,
            $articleMapper,
            $bulkOperation,
            $config,
            $log
        );
    }
}