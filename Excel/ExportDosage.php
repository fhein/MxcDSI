<?php

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Shopware\Components\Model\ModelManager;

class ExportDosage extends AbstractProductExport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $products;

    protected function registerColumns()
    {
        parent::registerColumns();
        $this->registerColumn('dosage');
    }

    public function setSheetData()
    {
        $this->sortColumns($this->data);

        $headers[] = array_keys($this->data[0]);
        $data = array_merge($headers, $this->data);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->fromArray($data);
    }

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->sheet->getHighestRowAndColumn();
        $bColumn = $this->sheet->getColumnDimension('B');
        $bColumn->setAutoSize(false);
        $bColumn->setWidth(15);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->getStyle('F2:'. $highest['column'] . $highest['row'])
            ->getNumberFormat()->setFormatCode('@');
        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
        $range = $this->getRange(['F', 1, 'F', $highest['row']]);
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $range);
    }

    protected function loadRawExportData(): ?array
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products
            ?? $this->products = $this->modelManager->getRepository(Product::class)->getExcelExportAroma();
    }
}