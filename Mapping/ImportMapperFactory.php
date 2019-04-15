<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
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
        $articleTool = $container->get(ArticleTool::class);
        $propertyMapper = $container->get(PropertyMapper::class);
        $productMapper = $container->get(ProductMapper::class);
        $detailMapper = $container->get(DetailMapper::class);
        $bulkOperation = new BulkOperation($container->get('modelManager'), $log);
        return new ImportMapper(
            $modelManager,
            $articleTool,
            $apiClient,
            $propertyMapper,
            $productMapper,
            $detailMapper,
            $bulkOperation,
            $config,
            $log
        );
    }
}