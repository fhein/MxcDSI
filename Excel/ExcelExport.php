<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;

class ExcelExport
{
    protected $exporters;

    const FILENAME = 'vapee.export.xlsx';

    protected $excelFile = __DIR__ . '/../Config/' . self::FILENAME;

    public function __construct(array $exporters)
    {
        $this->exporters = $exporters;
    }

    /**
     * @return string
     */
    public function getExcelFile(): string
    {
        return $this->excelFile;
    }

    public function export() {
        $spreadSheet = new Spreadsheet();
        /** @var ExportPrices $exporter */
        foreach ($this->exporters as $title => $exporter) {
            $workSheet = $spreadSheet->createSheet();
            $workSheet->setTitle($title);
            $exporter->export($workSheet);
        }
        $spreadSheet->removeSheetByIndex(0);
        $writer = new Writer($spreadSheet);
        $writer->save($this->excelFile);
    }
}