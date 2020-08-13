<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcCommons\Toolbox\Shopware\TaxTool;
use PhpOffice\PhpSpreadsheet\Style\Border;
use MxcCommons\Defines\Constants;

class ExportPriceIssues extends AbstractProductExport implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /** @var array */
    protected $models;

    private $customerGroupKeys;

    protected function registerColumns()
    {
        parent::registerColumns();
        $this->registerColumn('Product Number');
        $this->registerColumn('Options');
        $this->registerColumn('EK Netto');
        $this->registerColumn('EK Brutto');
        $this->registerColumn('UVP Brutto');
        $this->registerColumn('Marge UVP');
        $this->registerColumn('Comment');
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
        /** @var Product $product */
        foreach ($products as $product) {
            $tempData = [];
            $info = $this->getColumns();

            $variants = $product->getVariants();
            /** @var Variant $variant */
            $eks = [];
            $uvps = [];
            $margin = [];
            foreach ($variants as $variant) {
                if (! $this->isSinglePack($variant)) continue;
                $info['icNumber'] = $variant->getIcNumber();
                $purchasePrice = floatVal($variant->getPurchasePrice());
                $eks[] = $purchasePrice;
                $info['EK Netto'] = $purchasePrice;
                $taxFactor = TaxTool::getCurrentVatPercentage() / 100 + 1;
                $info['EK Brutto'] = $purchasePrice * $taxFactor;
                $uvp = floatVal($variant->getRecommendedRetailPrice());
                $uvps[] = $uvp;
                $info['UVP Brutto'] = $uvp;
                $margin[] = ($uvp - ($purchasePrice * $taxFactor))/$uvp * 100;
                $options = $variant->getOptions();
                $optionNames = [];
                /** @var Option $option */
                foreach ($options as $option) {
                    $optionName = $option->getName();
                    if ($optionName === '1er Packung') continue;
                    $optionNames[] = $option->getIcGroup()->getName() . ': ' . $option->getName();
                }
                $optionText = implode(', ', $optionNames);
                $info['Options'] = $optionText;
                $info['type'] = $product->getType();
                $info['supplier'] = $product->getSupplier();
                $info['brand'] = $product->getBrand();
                $info['name'] = $product->getName();
                $info['Product Number'] = $product->getIcNumber();

                $tempData[] = $info;
            }
            $comments = [];
            if (count(array_unique($eks)) !== 1) {
                $comments[] = 'EK';
            }
            if (count(array_unique($uvps)) !== 1) {
                $comments[] = 'UVP';
            }
            if (min($margin) < 30) {
                $comments[] = 'margin';
            }
            if (empty($comments)) continue;
            $comments = implode(', ', $comments);
            foreach ($tempData as $info) {
                $info['Comment'] = $comments;
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
            [$this->columns['UVP Brutto'], 1, $this->columns['Marge UVP'], $highest['row']],
        ];
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

        $range = $this->getRange(['H','2','K', $highest['row']]);
        $this->sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00');

        foreach (range('H', $highest['column']) as $col) {
            $this->sheet->getColumnDimension($col)->setWidth(16);
        }
        //Comments
        $this->sheet->getColumnDimension('L')->setWidth(80);

        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
    }

    protected function setConditionalStyles()
    {
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