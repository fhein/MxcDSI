<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use Shopware\Models\Customer\Group;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ExportPrices extends AbstractProductExport
{
    /** @var array */
    protected $models;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    private $customerGroupKeys;

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
        $this->registerColumn('EK Netto');
        $this->registerColumn('EK Brutto');
        $this->registerColumn('UVP Brutto alt');
        $this->registerColumn('UVP Brutto');
        $this->registerColumn('Marge UVP');
        $this->registerColumn('Dampfplanet');
        $this->registerColumn('MaxVapor');
        $customerGroupKeys = $this->getCustomerGroupKeys();
        foreach ($customerGroupKeys as $key) {
            if ($key === 'H') continue;
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
                $price = floatVal($variant->getPurchasePrice());
                $info['EK Netto'] = $price;
                $info['EK Brutto'] = $price * 1.19;
                $price = floatVal($variant->getRecommendedRetailPrice());
                $info['UVP Brutto'] = $price;
                $price = floatVal($variant->getRecommendedRetailPriceOld());
                $info['UVP Brutto alt'] = $price;
                $price = floatVal($variant->getPurchasePriceOld());
                $info['EK Netto alt'] = $price;
                $info['Dampfplanet'] = $variant->getRetailPriceDampfplanet();
                $info['MaxVapor'] = $variant->getRetailPriceMaxVapor();
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

                $customerGroupKeys = $this->getCustomerGroupKeys();
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

            $customerGroupKeys = $this->getCustomerGroupKeys();
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
        $options = $model->getOptions();

        $pattern = 'PACKUNG' . MXC_DELIMITER_L1;
        if (strpos($options, $pattern) === false) return true;

        $pattern .= '1er Packung';
        if (strpos($model->getOptions(), $pattern) !== false) return true;

        return false;
    }

    protected function setPriceMarginBorders()
    {
        $highest = $this->getHighestRowAndColumn();
        $columnRanges = [
            [$this->columns['UVP Brutto alt'], 1, $this->columns['Marge UVP'], $highest['row']],
        ];
        $customerGroupKeys = $this->getCustomerGroupKeys();
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
        // set old uvp and old purchase price text color grey
        $columns = [$this->columns['UVP Brutto alt'], $this->columns['EK Netto alt']];
        foreach ($columns as $column) {
            $range = $this->getRange([$column, 2, $column, $highest['row']]);
            $this->sheet->getStyle($range)->getFont()->getColor()->setARGB('FF808080');
        }

        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
        $this->setPriceMarginBorders();
        $range = [ $this->columns['Dampfplanet'], 1, $this->columns['andere'], $highest['row']];
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $this->getRange($range));
        $range = [ $this->columns['EK Netto alt'], 1, $this->columns['EK Brutto'], $highest['row']];
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
        $this->setConditionalFormat('MaxVapor',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_EQUAL,
            '""',
            'C5D9F1');
        $this->setConditionalFormat('andere',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_EQUAL,
            '""',
            'C5D9F1');
        $this->setConditionalFormat('UVP Brutto',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_NOTEQUAL,
            'UVP Brutto alt',
            'FFC000');
        $this->setConditionalFormat('EK Netto',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_NOTEQUAL,
            'EK Netto alt',
            'FFC000');
        foreach ($this->getCustomerGroupKeys() as $key) {
            $column = 'Marge ' . $key;
            $this->setConditionalFormat($column,
                Conditional::CONDITION_CELLIS,
                Conditional::OPERATOR_LESSTHAN,
                25,
                'FFCCCC');
        }
    }

    protected function getCustomerGroupKeys()
    {
        if ($this->customerGroupKeys !== null) return $this->customerGroupKeys;

        $this->modelManager = Shopware()->Models();
        $customerGroups = $this->modelManager->getRepository(Group::class)->findAll();
        /** @var Group $customerGroup */
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroupKeys[] = $customerGroup->getKey();
        }
        return $this->customerGroupKeys;
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