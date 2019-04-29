<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportMapping extends AbstractProductExport
{
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

        $this->sheet->getColumnDimension('F')->setWidth(40);
        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
    }

    protected function loadRawExportData(): ?array
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->modelManager->getRepository(Product::class)->getExcelExportMapping();
    }
}