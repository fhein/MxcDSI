<?php

namespace MxcDropshipInnocigs\Excel;

use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Price;

class ExportPrices extends AbstractProductExport
{
    /** @var array */
    protected $models;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    public function __construct(
        ModelManager $modelManager,
        PriceMapper $priceMapper,
        LoggerInterface $log
    ) {
        parent::__construct($modelManager, $log);
        $this->priceMapper = $priceMapper;
    }

    protected function registerColumns()
    {
        parent::registerColumns();
        $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
        foreach ($customerGroupKeys as $key) {
            $this->registerColumn('VK Brutto ' . $key);
            $this->registerColumn('Marge ' . $key);
        }
    }

    protected function getMarginColumnFormula(string $cellRetail, string $cellPurchase)
    {
        $condition = "AND(ISNUMBER({$cellRetail}), {$cellRetail}<>0)";
        $then = "({$cellRetail}-{$cellPurchase})/{$cellRetail}*100";
        $else = '""';
        $statement = "=IF({$condition},{$then},{$else})";
        return $statement;
    }

    protected function setSheetData()
    {
        $products = $this->data;
        $data = [];
        $headers = null;
        foreach ($products as $product) {
            $data[] = $this->getProductInfo($product);
        }
        $headers[] = array_keys($data[0]);
        $this->sortColumns($data);
        usort($data, [$this, 'compare']);

        $row = 2;
        foreach ($data as &$record) {
            $cellPurchase = $this->getRange([$this->getColumn('EK Brutto'), $row]);
            $cellRetail = $this->getRange([$this->getColumn('UVP Brutto'), $row]);
            $record['Marge UVP'] = $this->getMarginColumnFormula($cellRetail, $cellPurchase);

            $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
            foreach ($customerGroupKeys as $key) {
                $cellRetail = $this->getRange([$this->getColumn('VK Brutto ' . $key), $row]);
                $record['Marge ' . $key] = $this->getMarginColumnFormula($cellRetail, $cellPurchase);
            }

            $row++;
        }

        $data = array_merge($headers, $data);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->fromArray($data);
    }

    protected function getProductInfo(Product $product)
    {
        $info = $this->getColumns();
        $info['icNumber'] = $product->getIcNumber();
        $info['type'] = $product->getType();
        $info['supplier'] = $product->getSupplier();
        $info['brand'] = $product->getBrand();
        $info['name'] = $product->getName();
        list($info['EK Netto'], $info['EK Brutto'], $info['UVP Brutto']) = $this->getPrices($product->getVariants());
        $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
        $shopwarePrices = $this->getCurrentPrices($product);
        foreach ($customerGroupKeys as $key) {
            $info['VK Brutto ' . $key] = $shopwarePrices[$key] ?? null;
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
            $price = floatVal(str_replace(',', '.', $variant->getPurchasePrice()));
            if ($price > $purchasePrice) {
                $purchasePrice = $price;
            }
            $price = floatVal(str_replace(',', '.', $variant->getRecommendedRetailPrice()));
            if ($price > $retailPrice) {
                $retailPrice = $price;
            }
        }

        return [$purchasePrice, $purchasePrice * 1.19, $retailPrice];
    }

    protected function isSinglePack(Variant $variant)
    {
        $model = $this->getModels()[$variant->getIcNumber()];
        if (strpos($model->getOptions(), '1er Packung') !== false) {
            return true;
        }
        return false;
    }

    protected function setPriceMarginBorders()
    {
        $highest = $this->getHighestRowAndColumn();
        $columnRanges = [
            [$this->columns['UVP Brutto'], 1, $this->columns['Marge UVP'], $highest['row']]
        ];
        $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
        foreach ($customerGroupKeys as $key) {
            $columnRanges[] = [$this->columns['VK Brutto ' . $key], 1, $this->columns['Marge ' . $key], $highest['row']];
        }
        foreach ($columnRanges as $range) {
            $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $this->getRange($range));
        }
    }

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->sheet->getHighestRowAndColumn();
        $range = $this->getRange(['F','2',$highest['column'], $highest['row']]);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00');
        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
        $this->setPriceMarginBorders();
    }

    protected function getModels()
    {
        return $this->models ?? $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
    }

    protected function loadRawExportData(): ?array
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->modelManager->getRepository(Product::class)->getAllIndexed();
    }
}