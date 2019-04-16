<?php

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Shopware\Components\Model\ModelManager;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ImportPrices
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $indexMap;

    /** @var array */
    protected $models;

    /** @var array */
    protected $products;

    /** @var PriceMapper $priceTool */
    protected $priceTool;

    public function __construct(
        ModelManager $modelManager,
        PriceMapper $priceTool,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->priceTool = $priceTool;
    }

    public function import(Worksheet $sheet)
    {
        $records = $this->entitiesToArray($sheet->toArray());
        if (! is_array($records) || empty($records)) return;

        $keys = array_keys($records[0]);
        $this->indexMap = [];
        foreach ($keys as $key) {
            if (strpos($key, 'VK brutto') === 0) {
                $customerGroupKey = explode(' ', $key)[2];
                $this->indexMap[$key] = $customerGroupKey;
            }
        }

        foreach ($records as $record) {
            $this->updatePrices($record);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    protected function updatePrices(array $record)
    {
        /** @var Product $product */
        $product = $this->getProducts()[$record['icNumber']];
        if (! $product) return;

        $prices = [];
        $uvp = $record['UVP brutto'];
        $uvp = $uvp = '' ? null : $uvp;

        foreach ($this->indexMap as $column => $customerGroup) {
            $price = $record[$column];
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
            $this->priceTool->setRetailPrices($variant);
        }
    }

    protected function isSinglePack(Variant $variant)
    {
        $model = $this->getModels()[$variant->getIcNumber()];
        if (strpos($model->getOptions(), '1er Packung') !== false) {
            return true;
        }
        return false;
    }

    protected function entitiesToArray(array $entities)
    {
        $headers = null;
        foreach ($entities as &$entity) {
            if (! $headers) {
                $headers = $entity;
                continue;
            }
            $entity = array_combine($headers, $entity);
        }
        // remove header entity
        array_shift($entities);
        return $entities;

    }

    protected function getProducts()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products ?? $this->products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
    }

    protected function getModels()
    {
        return $this->models ?? $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
    }
}