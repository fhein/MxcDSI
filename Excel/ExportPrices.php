<?php

namespace MxcDropshipInnocigs\Excel;

use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Price;

class ExportPrices
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

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    public function __construct(
        ModelManager $modelManager,
        PriceMapper $priceMapper,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->priceMapper = $priceMapper;
    }

    public function export(Worksheet $sheet)
    {
        $data = $this->getExportData();

        /** @noinspection PhpUnhandledExceptionInspection */
        $sheet->fromArray($data);
        $this->formatSheet($sheet);
    }

    public function getExportData()
    {
        $products = $this->getProducts();
        $data = [];
        $headers = null;
        foreach ($products as $product) {
            $data[] = $this->getProductInfo($product);
        }
        $headers[] = array_keys($data[0]);
        usort($data, [$this, 'compare']);
        $data = array_merge($headers, $data);
        return $data;
    }

    protected function getProductInfo(Product $product)
    {
        $info['icNumber'] = $product->getIcNumber();
        $info['type'] = $product->getType();
        $info['supplier'] = $product->getSupplier();
        $info['brand'] = $product->getBrand();
        $info['name'] = $product->getName();
        list($info['EK netto'], $info['UVP brutto']) = $this->getPrices($product->getVariants());
        $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
        $shopwarePrices = $this->getCurrentPrices($product);
        foreach ($customerGroupKeys as $key) {
            $info['VK brutto ' . $key] = $shopwarePrices[$key] ?? '';
        }
        return $info;
    }

    protected function getCurrentPrices(Product $product)
    {
        $variants = $product->getVariants();
        /** @var Variant $variant */
        $detailPrices = [];
        foreach ($variants as $variant) {
            if (! $this->isSinglePack($variant)) continue;
            $detail = $variant->getDetail();
            if (! $detail) continue;
            $shopwarePrices = $detail->getPrices();
            /** @var Price $price */
            foreach ($shopwarePrices as $price) {
                $detailPrices[$price->getCustomerGroup()->getKey()][$variant->getIcNumber()] = $price->getPrice();
            }
        }
        $prices = [];
        foreach ($detailPrices as $key => $price) {
            // $prices[$key] = str_replace ('.', ',', strval(max($price) * 1.19));
            $prices[$key] = max($price) * 1.19;
        }

        return $prices;
    }

    protected function getPrices(Collection $variants)
    {
        $purchasePrice = 0.0;
        $retailPrice = 0.0;
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            /** @var Model $model */
            if (! $this->isSinglePack($variant)) continue;
            $price = floatval($variant->getPurchasePrice());
            if ($price > $purchasePrice) {
                $purchasePrice = $price;
            }
            $price = floatval($variant->getRecommendedRetailPrice());
            if ($price > $retailPrice) {
                $retailPrice = $price;
            }
        }
        return [$purchasePrice, $retailPrice];
    }

    protected function isSinglePack(Variant $variant)
    {
        $model = $this->getModels()[$variant->getIcNumber()];
        if (strpos($model->getOptions(), '1er Packung') !== false) {
            return true;
        }
        return false;
    }

    /**
     * @param Worksheet $sheet
     */
    protected function formatSheet(Worksheet $sheet): void
    {
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('E')->setWidth(80);
        $highest = $sheet->getHighestRowAndColumn();

        foreach (range('F', $highest['column']) as $col) {
            $sheet->getColumnDimension($col)->setWidth(16);

        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $sheet->freezePane('A2');

        /** @noinspection PhpUnhandledExceptionInspection */
        $sheet->getStyle('F2:'. $highest['column'] . $highest['row'])->getNumberFormat()->setFormatCode('0.00');
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

    /**
     * Callback for usort
     *
     * @param $one
     * @param $two
     * @return bool
     */
    protected function compare($one, $two)
    {
        $t1 = $one['type'];
        $t2 = $two['type'];
        if ($t1 > $t2) {
            return true;
        }
        if ($t1 === $t2) {
            $s1 = $one['supplier'];
            $s2 = $two['supplier'];
            if ($s1 > $s2) {
                return true;
            }
            if ($s1 === $s2) {
                return $one['brand'] > $two['brand'];
            }
        }
        return false;
    }
}