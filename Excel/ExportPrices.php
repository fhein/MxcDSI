<?php

namespace MxcDropshipInnocigs\Excel;

use Doctrine\Common\Collections\Collection;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use PhpOffice\PhpSpreadsheet\Style\Border;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ExportPrices extends AbstractProductExport
{
    /** @var array */
    protected $models;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    public function __construct(PriceMapper $priceMapper)
    {
        $this->priceMapper = $priceMapper;
    }

    protected function registerColumns()
    {
        parent::registerColumns();
        $this->registerColumn('EK Netto');
        $this->registerColumn('EK Brutto');
        $this->registerColumn('Dampfplanet');
        $this->registerColumn('andere');
        $this->registerColumn('UVP Brutto');
        $this->registerColumn('Marge UVP');
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
        //usort($data, [$this, 'compare']);

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
        $this->log->debug(var_export($info, true));
        $info['icNumber'] = $product->getIcNumber();
        $info['type'] = $product->getType();
        $info['supplier'] = $product->getSupplier();
        $info['brand'] = $product->getBrand();
        $info['name'] = $product->getName();
        list($info['EK Netto'], $info['EK Brutto'], $info['UVP Brutto']) = $this->getInnocigsPrices($product->getVariants());
        $info['Dampfplanet'] = $product->getRetailPriceDampfPlanet();
        $info['andere'] = $product->getRetailPriceOthers();
        $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
        $vapeePrices = $this->getVapeePrices($product);
        foreach ($customerGroupKeys as $key) {
            $price = $vapeePrices[$key] ?? null;
            $price = $price === $info['UVP Brutto'] ? null : $price;
            $info['VK Brutto ' . $key] = $price;
        }
        return $info;
    }

    /**
     * Get the maximum retail prices for each variant. Single Pack only (1er Packung)
     *
     * @param Product $product
     * @return array
     */
    protected function getVapeePrices(Product $product)
    {
        $variants = $product->getVariants();
        $variantPrices = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            if (! $this->isSinglePack($variant)) continue;
            $sPrices = explode(MXC_DELIMITER_L2, $variant->getRetailPrices());
            foreach ($sPrices as $sPrice) {
                list($key, $price) = explode(MXC_DELIMITER_L1, $sPrice);
                $variantPrices[$key][$variant->getIcNumber()] = floatVal(str_replace(',', '.', $price));
            }
        }
        $prices = [];
        foreach ($variantPrices as $key => $price) {
            $prices[$key] = max($price);
        }
        return $prices;
    }

    /**
     * Get the maximum purchase price and recommended retail price for each variant. Single Pack only (1er Packung)
     *
     * @param Collection $variants
     * @return array
     */
    protected function getInnocigsPrices(Collection $variants)
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
            [$this->columns['UVP Brutto'], 1, $this->columns['Marge UVP'], $highest['row']],
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

        foreach (range('F', $highest['column']) as $col) {
            $this->sheet->getColumnDimension($col)->setWidth(16);
        }

        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
        $this->setPriceMarginBorders();
        $range = [ $this->columns['Dampfplanet'], 1, $this->columns['andere'], $highest['row']];
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $this->getRange($range));
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