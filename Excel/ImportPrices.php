<?php

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ImportPrices extends AbstractProductImport
{
    /** @var array */
    protected $indexMap;

    /** @var array */
    protected $models;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    public function __construct(
        PriceMapper $priceMapper
    ) {
        $this->priceMapper = $priceMapper;
    }

    protected function processImportData()
    {
        $keys = array_keys($this->data[0]);
        $this->indexMap = [];
        foreach ($keys as $key) {
            if (strpos($key, 'VK Brutto') === 0) {
                $customerGroupKey = explode(' ', $key)[2];
                $this->indexMap[$key] = $customerGroupKey;
            }
        }
        $this->updatePrices();

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    protected function updatePrices()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
        $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
        /** @var Product $product */
        foreach ($this->data as $record) {
            $product = $products[$record['icNumber']] ?? null;
            if (!$product) continue;
            // we use the prices sheet to fix supplier and brand also
            $product->setSupplier($record['supplier']);
            $product->setBrand($record['brand']);
            $product->setRetailPriceDampfPlanet($record['Dampfplanet']);
            $product->setRetailPriceOthers($record['andere']);

            $this->updateProductPrice($product, $record);
       }
    }

    protected function updateProductPrice(Product $product, array $record)
    {
        $prices = [];
        $uvp = $record['UVP Brutto'];
        $uvp = $uvp = '' ? null : $uvp;

        foreach ($this->indexMap as $column => $customerGroup) {
            $price = $record[$column];
            // @todo: Check type of empty price, because this does not work
            $price = $price === '' ? null : $price;
            $price = $price ?? $uvp;
            if ($price) {
                $prices[] = $customerGroup . MXC_DELIMITER_L1 . $price;
            }
        }
        $prices = implode(MXC_DELIMITER_L2, $prices);

        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            if (! $this->isSinglePack($variant)) continue;
            $variant->setRetailPrices($prices);
            $this->priceMapper->setRetailPrices($variant);
        }
    }

    protected function isSinglePack(Variant $variant)
    {
        $model = @$this->models[$variant->getIcNumber()];
        if (! $model) return false;
        if (strpos($model->getOptions(), '1er Packung') !== false) {
            return true;
        }
        return false;
    }
}