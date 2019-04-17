<?php

namespace MxcDropshipInnocigs\Excel;

use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Price;

class ExportPrices extends AbstractProductExport
{
    /** @var array */
    protected $indexMap;

    /** @var array */
    protected $models;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    protected $columnTemplate;
    private $columnIndex;
    
    private $fixedColumns = [
        'icNumber',
        'type',
        'supplier',
        'brand',
        'name',
        'EK Netto',
        'EK Brutto',
        'UVP Brutto',
        'Marge UVP',
    ];

    public function __construct(
        ModelManager $modelManager,
        PriceMapper $priceMapper,
        LoggerInterface $log
    ) {
        parent::__construct($modelManager, $log);
        $this->priceMapper = $priceMapper;
    }

    protected function setMarginColumn(array &$record, int $row, string $column, string $retailGross, string $cellPurchaseGross)
    {
        $cellRetailGross = $this->columnIndex[$retailGross] . $row;
        $condition = "AND(ISNUMBER({$cellRetailGross}), {$cellRetailGross}<>0)";
        $then = "({$cellRetailGross}-{$cellPurchaseGross})/{$cellRetailGross}*100";
        $else = '""';
        $record[$column] = "=IF({$condition},{$then},{$else})";;
    }

    protected function setSheetData(array $products)
    {
        $data = [];
        $headers = null;
        foreach ($products as $product) {
            $data[] = $this->getProductInfo($product);
        }
        $headers[] = array_keys($data[0]);
        usort($data, [$this, 'compare']);

        $idx = 2;
        foreach ($data as &$record) {
            $cellPurchaseGross = $this->columnIndex['EK Brutto']. $idx;
            $this->setMarginColumn($record, $idx, 'Marge UVP', 'UVP Brutto', $cellPurchaseGross);

            $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
            foreach ($customerGroupKeys as $key) {
                $this->setMarginColumn($record, $idx, 'Marge ' . $key, 'VK Brutto ' . $key, $cellPurchaseGross);
            }

            $idx++;
        }

        $data = array_merge($headers, $data);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->fromArray($data);
    }

    protected function getColumnTemplate()
    {
        if ($this->columnTemplate) return $this->columnTemplate;
        foreach ($this->fixedColumns as $key => $name) {
            $t[$name] = '';
            $c[$name] = $key;
        }
        $cidx = count($this->fixedColumns);
        $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
        foreach ($customerGroupKeys as $key) {
            $name = 'VK Brutto ' . $key;
            $t[$name] = '';
            $c[$name] = $cidx++;
            $name = 'Marge ' . $key;
            $t[$name] = '';
            $c[$name] = $cidx++;
        }
        $this->columnTemplate = $t;
        $this->columnIndex = array_map(function($i) { return chr($i + 65);}, $c);

        return $t;
    }

    protected function getProductInfo(Product $product)
    {
        $info = $this->getColumnTemplate();
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

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->sheet->getHighestRowAndColumn();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->getStyle('F2:'. $highest['column'] . $highest['row'])->getNumberFormat()->setFormatCode('0.00');
        $this->alternateRowColors();
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