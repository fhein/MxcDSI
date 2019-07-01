<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
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
        $this->registerColumn('Product Number');
        $this->registerColumn('options');
        $this->registerColumn('EK Netto alt');
        $this->registerColumn('UVP Brutto alt');
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
        /** @var Product $product */
        foreach ($products as $product) {
            $info = $this->getColumns();
            $info['type'] = $product->getType();
            $info['supplier'] = $product->getSupplier();
            $info['brand'] = $product->getBrand();
            $info['name'] = $product->getName();
            $info['Product Number'] = $product->getIcNumber();

            $variants = $product->getVariants();
            /** @var Variant $variant */
            foreach ($variants as $variant) {
                if (! $this->isSinglePack($variant)) continue;
                $info['icNumber'] = $variant->getIcNumber();
                $price = floatVal(str_replace(',', '.', $variant->getPurchasePrice()));
                $info['EK Netto'] = $price;
                $info['EK Brutto'] = $price * 1.19;
                $price = floatVal(str_replace(',', '.', $variant->getRecommendedRetailPrice()));
                $info['UVP Brutto'] = $price;
                $price = floatVal(str_replace(',', '.', $variant->getRecommendedRetailPriceOld()));
                $info['UVP Brutto alt'] = $price;
                $price = floatVal(str_replace(',', '.', $variant->getPurchasePriceOld()));
                $info['EK Netto alt'] = $price;
                $info['Dampfplanet'] = $variant->getRetailPriceDampfplanet();
                $info['andere'] = $variant->getRetailPriceOthers();
                $options = $variant->getOptions();
                $optionNames = [];
                /** @var Option $option */
                foreach ($options as $option) {
                    $optionName = $option->getName();
                    if ($optionName === '1er Packung') continue;
                    $optionNames[] = $option->getIcGroup()->getName() . ': ' . $option->getName();
                }
                $optionText = implode(', ', $optionNames);
                $info['options'] = $optionText;

                $customerGroupKeys = array_keys($this->priceMapper->getCustomerGroups());
                $vapeePrices = $this->getVapeePrices($variant);
                foreach ($customerGroupKeys as $key) {
                    $price = $vapeePrices[$key] ?? null;
                    if ($key !== 'EK') {
                        $price = $price === $info['UVP Brutto'] ? null : $price;
                    }
                    $info['VK Brutto ' . $key] = $price;
                }

                $data[] = $info;
            }
        }

        $headers[] = array_keys($data[0]);

        $this->sortColumns($data);
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
        $this->sheet->fromArray($data);

    }

    /**
     * Get the maximum retail prices for each variant. Single Pack only (1er Packung)
     *
     * @param Variant $variant
     * @return array
     */
    protected function getVapeePrices(Variant $variant)
    {
        $variantPrices = [];
        /** @var Variant $variant */
        $sPrices = explode(MXC_DELIMITER_L2, $variant->getRetailPrices());
        foreach ($sPrices as $sPrice) {
            list($key, $price) = explode(MXC_DELIMITER_L1, $sPrice);
            $variantPrices[$key] = floatVal(str_replace(',', '.', $price));
        }
        return $variantPrices;
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

        // options
        $this->sheet->getColumnDimension('G')->setWidth(60);

        // variant number
        $this->sheet->getColumnDimension('F')->setWidth(20);

        $range = $this->getRange(['H','2',$highest['column'], $highest['row']]);
        $this->sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00');

        foreach (range('H', $highest['column']) as $col) {
            $this->sheet->getColumnDimension($col)->setWidth(16);
        }

        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
        $this->setPriceMarginBorders();
        $range = [ $this->columns['Dampfplanet'], 1, $this->columns['andere'], $highest['row']];
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $this->getRange($range));
        $this->setConditionalStyles();
    }

    protected function setConditionalStyles(){
        $this->setConditionalFormat('VK Brutto EK',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_EQUAL,
            'UVP Brutto',
            '	C5D9F1');//light blue
        $this->setConditionalFormat('VK Brutto EK',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_GREATERTHAN,
            'UVP Brutto',
            'FFC000');//orange
        $this->setConditionalFormat('Dampfplanet',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_EQUAL,
            '""',
            'C5D9F1');
        $this->setConditionalFormat('andere',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_EQUAL,
            '""',
            'C5D9F1');
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