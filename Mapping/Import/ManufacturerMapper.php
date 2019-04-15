<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class ManufacturerMapper extends BaseImportMapper implements ProductMapperInterface
{
    /** @var array */
    protected $report;

    /** @var array */
    protected $mappings;

    public function __construct(ImportMappings $mappings, array $config, LoggerInterface $log)
    {
        parent::__construct($config, $log);
        $this->mappings = $mappings->getConfig();
    }

    public function map(Model $model, Product $product): void
    {
        $this->mapBrand($model, $product);
        $this->mapSupplier($model, $product);
    }

    protected function mapSupplier(Model $model, Product $product)
    {
        $supplier = $product->getSupplier();
        if ($supplier === null) {
            $mapping = $this->mappings[$product->getIcNumber()] ?? [];
            $manufacturer = $model->getManufacturer();
            $supplier = $mapping['supplier'];
            if (!$supplier) {
                if (!in_array($manufacturer, $this->config['innocigs_brands'])) {
                    $supplier = $this->config['manufacturers'][$manufacturer]['supplier'] ?? $manufacturer;
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
            $brand = $mapping['brand'] ?? $this->config['manufacturers'][$manufacturer]['brand'] ?? $manufacturer;
            $product->setBrand($brand);
        }
        $this->report['brand'][$product->getBrand()] = true;
    }
}