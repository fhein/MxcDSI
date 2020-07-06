<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportEcigMetaData extends AbstractProductExport implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /** @var array */
    protected $models;

    private $customerGroupKeys;

    protected function registerColumns()
    {
        parent::registerColumns();
        $this->registerColumn('Power');
        $this->registerColumn('Cell Change');
        $this->registerColumn('Capacity');
        $this->registerColumn('# Cells');
        $this->registerColumn('Head Change');
    }

    protected function setSheetData()
    {
        $products = $this->data;
        $data = [];

        $headers = null;
        /** @var Product $product */
        foreach ($products as $product) {
            $info = $this->getColumns();
            $info['icNumber'] = $product['icNumber'];
            $info['type'] = $product['type'];
            $info['supplier'] = $product['supplier'];
            $info['brand'] = $product['brand'];
            $info['name'] = $product['name'];
            $info['Power'] = $product['power'];
            $info['Head Change'] = intval($product['headChangeable']);
            $data[] = $info;
        }

        $headers[] = array_keys($data[0]);

        $this->sortColumns($data);

        $data = array_merge($headers, $data);
        $this->sheet->fromArray($data);
    }

    protected function setPriceMarginBorders()
    {
//        $highest = $this->getHighestRowAndColumn();
//        $columnRanges = [
//            [$this->columns['UVP Brutto'], 1, $this->columns['Marge UVP'], $highest['row']],
//        ];
//        foreach ($columnRanges as $range) {
//            $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $this->getRange($range));
//        }
    }

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->sheet->getHighestRowAndColumn();

        $range = $this->getRange(['H','2','K', $highest['row']]);
        // $this->sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00');

        foreach (range('G', $highest['column']) as $col) {
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
        return $this->modelManager->getRepository(Product::class)->getExcelExportEcigMetaData();
    }

}