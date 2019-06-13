<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use RuntimeException;

class PropertyMapper implements LoggerAwareInterface, ModelManagerAwareInterface, ClassConfigAwareInterface
{
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;
    use LoggerAwareTrait;

    /** @var array */
    protected $productMappers;

    /** @var array */
    protected $variantMappers;

    /** @var RegularExpressions $regularExpressions */
    protected $regularExpressions;

    /** @var AssociatedProductsMapper $associatedProductsMapper */
    protected $associatedProductsMapper;

    /** @var array */
    protected $mappings;

    /** @var array */
    protected $products = null;

    protected $models = null;

    public function __construct(
        ProductMappings $mappings,
        AssociatedProductsMapper $associatedProductsMapper,
        RegularExpressions $regularExpressions,
        array $productMappers,
        array $variantMappers
    ) {
        $this->productMappers = $productMappers;
        $this->associatedProductsMapper = $associatedProductsMapper;
        $this->mappings = $mappings;
        $this->regularExpressions = $regularExpressions;
        $this->variantMappers = $variantMappers;
        $this->reset();
    }

    public function reset()
    {
        $this->models = null;
        $this->products = null;
    }

    public function mapProperties(array $products)
    {
        if (@$this->classConfig['settings']['checkRegularExpressions'] === true) {
            if (! $this->regularExpressions->check()) {
                throw new RuntimeException('Regular expression failure.');
            }
        }
        $this->reset();
        $models = $this->getModels();
        if (! $models) {
            $this->log->debug(__FUNCTION__ . ': no import models found.');
            return;
        }

        /** @var Product $product */
        foreach ($products as $product) {
            $variants = $product->getVariants();
            $first = true;
            /** @var Variant $variant */
            foreach ($variants as $variant) {
                $model = $models[$variant->getIcNumber()];
                // do nothing if we do not know the model
                if (! $model) continue;
                if ($first) {
                    $this->mapModelToProduct($model, $product);
                    $first = false;
                }
                $this->mapModelToVariant($model, $variant);
            }
        }
        $this->associatedProductsMapper->map($products);
        $this->report();
        $this->modelManager->flush();
    }

    /**
     * Set all properties of Product maintained by PropertyMapper
     *
     * @param Model $model
     * @param Product $product
     */
    public function mapModelToProduct(Model $model, Product $product)
    {
        foreach ($this->productMappers as $productMapper) {
            $productMapper->map($model, $product);
        }
    }

    /**
     * Set all properties of Variant maintained by PropertyMapper
     *
     * @param Model $model
     * @param Variant $variant
     */
    public function mapModelToVariant(Model $model, Variant $variant)
    {
        foreach ($this->variantMappers as $mapper) {
            $mapper->map($model, $variant);
        }
    }

    public function mapCategory(Model $model, Product $product) {
        $this->productMappers['category']->map($model, $product);
    }

    public function mapName(Model $model, Product $product) {
        $this->productMappers['name']->map($model, $product);
    }

    public function mapManufacturer(Model $model, Product $product)
    {
        $this->productMappers['manufacturer']->map($model, $product);
    }

    public function mapDescription(Model $model, Product $product)
    {
        $this->productMappers['description']->map($model, $product);
    }

    public function mapGroupName($name)
    {
        return $this->classConfig['group_names'][$name] ?? $name;
    }

    public function mapOptionName($name)
    {
        $mapping = $this->classConfig['option_names'][$name] ?? $name;
        return str_replace('weiss', 'weiß', $mapping);
    }

    protected function getModels()
    {
        return $this->models ?? $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
    }

    protected function report()
    {
        foreach ($this->productMappers as $productMapper) {
            $productMapper->report();
        }
    }
}