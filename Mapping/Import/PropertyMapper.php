<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use RuntimeException;
use Shopware\Components\Model\ModelManager;

class PropertyMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var array */
    protected $productMappers;

    /** @var array */
    protected $variantMappers;

    /** @var RegularExpressions $regularExpressions */
    protected $regularExpressions;

    /** @var AssociatedProductsMapper $associatedProductsMapper */
    protected $associatedProductsMapper;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var Reporter $reporter */
    protected $reporter;

    /** @var Flavorist $flavorist */
    protected $flavorist;

    /** @var array */
    protected $config;

    /** @var array */
    protected $mappings;

    /** @var array */
    protected $report;
    protected $products = null;

    protected $Models = null;

    public function __construct(
        ModelManager $modelManager,
        ImportMappings $mappings,
        AssociatedProductsMapper $associatedProductsMapper,
        RegularExpressions $regularExpressions,
        Flavorist $flavorist,
        Reporter $reporter,
        array $productMappers,
        array $variantMappers,
        array $config,
        LoggerInterface $log)
    {
        $this->productMappers = $productMappers;
        $this->associatedProductsMapper = $associatedProductsMapper;
        $this->config = $config;
        $this->flavorist = $flavorist;
        $this->log = $log;
        $this->mappings = $mappings;
        $this->modelManager = $modelManager;
        $this->regularExpressions = $regularExpressions;
        $this->reporter = $reporter;
        $this->variantMappers = $variantMappers;
        $this->reset();
    }

    public function reset()
    {
        $this->report = [];
        $this->Models = null;
        $this->products = null;
    }

    public function mapProperties(array $products)
    {
        if ($this->config['settings']['checkRegularExpressions'] === true) {
            if (! $this->regularExpressions->check()) {
                throw new RuntimeException('Regular expression failure.');
            }
        }
        $this->reset();
        $models = $this->getModels();
        if (! $models || ! $products) {
            $this->log->debug(__FUNCTION__ . ': no import models or products found.');
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
        $this->productMappers['name']->report();

        ($this->reporter)($this->report, $this->config);
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

    public function mapProductCategory(Model $model, Product $product) {
        $this->productMappers['category']->map($model, $product);
    }

    public function mapProductName(Model $model, Product $product) {
        $this->productMappers['name']->map($model, $product);
    }

    public function mapProductManufacturer(Model $model, Product $product)
    {
        $this->productMappers['manufacturer']->map($model, $product);
    }

    public function mapGroupName($name)
    {
        return $this->config['group_names'][$name] ?? $name;
    }

    public function mapOptionName($name)
    {
        $mapping = $this->config['option_names'][$name] ?? $name;
        return str_replace('weiss', 'weiß', $mapping);
    }

    protected function getModels()
    {
        return $this->Models ?? $this->Models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
    }
}