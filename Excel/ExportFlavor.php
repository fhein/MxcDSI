<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportFlavor extends AbstractProductExport
{
    public function setSheetData()
    {
        $this->sortColumns($this->data);

        $headers[] = array_keys($this->data[0]);
        $data = array_merge($headers, $this->data);
        $this->sheet->fromArray($data);
    }

    protected function registerColumns()
    {
        parent::registerColumns();
        $this->registerColumn('flavor');
        $this->registerColumn('content');
        $this->registerColumn('capacity');
    }

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->getHighestRowAndColumn();
        $this->sheet->getStyle('F2:'. $highest['column'] . $highest['row'])
            ->getNumberFormat()->setFormatCode('@');
        $this->sheet->getColumnDimension('F')->setWidth(80);

        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $range = $this->getRange(['F', 1, 'F', $highest['row']]);
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $range);
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
    }

    protected function loadRawExportData(): ?array
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->modelManager->getRepository(Product::class)->getExcelExportFlavoredProducts();
    }
}