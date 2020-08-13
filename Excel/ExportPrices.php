<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Mapping\Shopware\PriceEngine;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcCommons\Toolbox\Shopware\TaxTool;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use MxcCommons\Defines\Constants;

class ExportPrices extends AbstractProductExport implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /** @var array */
    protected $models;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    /** @var PriceEngine  */
    protected $priceEngine;

    private $customerGroupKeys;

    private $excludedCustomerGroupKeys = [ 'H', 'FR', 'MA'];

    public function __construct(PriceEngine $priceEngine, PriceMapper $priceMapper)
    {
        $this->priceEngine = $priceEngine;
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
        $this->registerColumn('andere');
        $customerGroupKeys = $this->getCustomerGroupKeys();
        foreach ($customerGroupKeys as $key) {
            $this->registerColumn('VK Brutto ' . $key);
            $this->registerColumn('Marge ' . $key);
            $this->registerColumn('Corrected VK ' . $key);
            $this->registerColumn('Marge C ' . $key);
        }
    }

    protected function getMarginColumnFormula(string $cellRetail, string $cellPurchase)
    {
        $condition = "AND(ISNUMBER({$cellRetail}), {$cellRetail}<>0)";
        $then = "({$cellRetail}-{$cellPurchase})/{$cellRetail}*100";
        $else = '""';
        return "=IF({$condition},{$then},{$else})";
    }

    protected function setSheetData()
    {
        $products = $this->data;
        $data = [];
        $headers = null;
        $count = 0;
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

                $vatFactor = (1 + TaxTool::getCurrentVatPercentage() / 100);

                if (! $this->isSinglePack($variant)) continue;
                $info['icNumber'] = $variant->getIcNumber();
                $price = floatVal($variant->getPurchasePrice());
                $info['EK Netto'] = $price;
                $info['EK Brutto'] = round($price * $vatFactor, 2);
                $price = floatVal($variant->getRecommendedRetailPrice());
                $info['UVP Brutto'] = round($price * $vatFactor, 2);
                $price = floatVal($variant->getRecommendedRetailPriceOld());
                $info['UVP Brutto alt'] = round($price * $vatFactor, 2);
                $price = floatVal($variant->getPurchasePriceOld());
                $info['EK Netto alt'] = round($price, 2);
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
                $vapeePrices = $this->priceEngine->getRetailPrices($variant);
                $correctedRetailPrices = $this->priceEngine->getCorrectedRetailPrices($variant);
                foreach ($customerGroupKeys as $key) {
                    $price = $vapeePrices[$key] ?? null;
                    $correctedPrice = $correctedRetailPrices[$key] ?? null;
                    if ($key !== 'EK') {
                        $price = $price === $info['UVP Brutto'] ? null : $price;
                    }
                    $info['VK Brutto ' . $key] = round($this->priceEngine->beautifyPrice($price * $vatFactor), 2);
                    $info['Corrected VK ' . $key] = round($this->priceEngine->beautifyPrice( $correctedPrice * $vatFactor), 2);
                }

                $data[] = $info;
            }
            // if ($count++ > 1) break;
        }
        // $this->priceEngine->report();

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
                $cellRetail = $this->getRange([$this->getColumn('Corrected VK ' . $key), $row]);
                $record['Marge C ' . $key] = $this->getMarginColumnFormula($cellRetail, $cellPurchase);

            }

            $row++;
        }

        $data = array_merge($headers, $data);
        $this->sheet->fromArray($data);

    }

    protected function isSinglePack(Variant $variant)
    {

        $model = $this->getModels()[$variant->getIcNumber()];
        if ($model === null) {
            $this->log->debug('No model for : '. $variant->getIcNumber(). '. Ignoring ' . $variant->getProduct()->getName());
            return false;
        }
        $options = $model->getOptions();

        $pattern = 'PACKUNG' . Constants::DELIMITER_L1;
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

            $this->setConditionalFormat($column,
                Conditional::CONDITION_CELLIS,
                Conditional::OPERATOR_LESSTHAN,
                25,
                'FFCCCC');

            $this->setConditionalFormat('Corrected VK ' . $key,
                Conditional::CONDITION_CELLIS,
                Conditional::OPERATOR_NOTEQUAL,
                'VK Brutto ' . $key,
                'FFC000');
        }
    }

    protected function getCustomerGroupKeys()
    {
        if ($this->customerGroupKeys !== null) return $this->customerGroupKeys;
        $customerGroupKeys = $this->priceEngine->getCustomerGroupKeys();
        $this->customerGroupKeys = array_values(array_diff($customerGroupKeys, $this->excludedCustomerGroupKeys));
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