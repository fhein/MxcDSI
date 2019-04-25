<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class ManufacturerMapper implements ProductMapperInterface, ClassConfigAwareInterface
{
    use ClassConfigAwareTrait;

    /** @var array */
    protected $report;

    /** @var array */
    protected $mappings;

    /** @var array */
    protected $innocigsBrands;

    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    public function map(Model $model, Product $product): void
    {
        $this->innocigsBrands = $this->classConfig['innocigs_brands'] ?? [];
        $this->mapBrand($model, $product);
        $this->mapSupplier($model, $product);
    }

    protected function mapSupplier(Model $model, Product $product)
    {
        $supplier = $product->getSupplier();
        if ($supplier === null) {
            $mapping = $this->mappings[$product->getIcNumber()] ?? [];
            $manufacturer = $model->getManufacturer();
            $supplier = $mapping['supplier'] ?? null;
            if (! $supplier) {
                if (!in_array($manufacturer, $this->innocigsBrands)) {
                    $supplier = @$this->classConfig['manufacturers'][$manufacturer]['supplier'] ?? $manufacturer;
                }
            }
            $product->setSupplier($supplier);
        }
        $this->report['supplier'][$product->getSupplier()] = true;
    }

    protected function mapBrand(Model $model, Product $product)
    {
        $brand = $product->getBrand();
        if ($brand === null) {
            $mapping = $this->mappings[$product->getIcNumber()] ?? [];
            $manufacturer = $model->getManufacturer();
            $brand = $mapping['brand'] ?? $this->classConfig['manufacturers'][$manufacturer]['brand'] ?? $manufacturer;
            $product->setBrand($brand);
        }
        $this->report['brand'][$product->getBrand()] = true;
    }
}