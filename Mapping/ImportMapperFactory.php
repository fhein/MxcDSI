<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportMapperFactory implements FactoryInterface
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
        $articleTool = $container->get(ArticleTool::class);
        $propertyMapper = $container->get(PropertyMapper::class);
        $categoryMapper = $container->get(CategoryMapper::class);
        $productMapper = $container->get(ProductMapper::class);
        $detailMapper = $container->get(DetailMapper::class);
        $bulkOperation = $container->get(BulkOperation::class);
        return $this->augment($container, new ImportMapper(
            $articleTool,
            $apiClient,
            $propertyMapper,
            $categoryMapper,
            $productMapper,
            $detailMapper,
            $bulkOperation
        ));
    }
}