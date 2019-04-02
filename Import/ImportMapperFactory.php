<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleManufacturerMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleNameMapper;
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
        $propertyMapper = $container->get(PropertyMapper::class);
        $articleMapper = $container->get(ArticleMapper::class);
        $bulkOperation = new BulkOperation($container->get('modelManager'), $log);
        $articleNameMapper = $container->get(ArticleNameMapper::class);
        $articleManufacturerMapper = $container->get(ArticleManufacturerMapper::class);
        return new ImportMapper(
            $modelManager,
            $apiClient,
            $propertyMapper,
            $articleNameMapper,
            $articleManufacturerMapper,
            $articleMapper,
            $bulkOperation,
            $config,
            $log
        );
    }
}