<?php

namespace MxcDropshipInnocigs\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;

class ExcelExport
{
    protected $exporters;

    protected $excelFile = __DIR__ . '/../Config/vapee.export.xlsx';

    public function __construct(array $exporters)
    {
        $this->exporters = $exporters;
    }

    public function export() {
        $spreadSheet = new Spreadsheet();
        /** @var ExportPrices $exporter */
        foreach ($this->exporters as $title => $exporter) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $workSheet = $spreadSheet->createSheet();
            $workSheet->setTitle($title);
            $exporter->export($workSheet);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $spreadSheet->removeSheetByIndex(0);
        $writer = new Writer($spreadSheet);
        /** @noinspection PhpUnhandledExceptionInspection */
        $writer->save($this->excelFile);
    }
}