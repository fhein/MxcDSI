<?php

namespace MxcDropshipInnocigs\Excel;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;

class ExcelImport
{
    protected $importers;

    protected $excelFile = __DIR__ . '/../Config/vapee.export.xlsx';

    public function __construct(array $importers)
    {
        $this->importers = $importers;
    }

    public function import($filepath = null) {
        /** @noinspection PhpUnhandledExceptionInspection */

        $importFile = $filepath ? $filepath : $this->excelFile;
        $spreadSheet = (new Reader())->load($importFile);
        foreach ($this->importers as $title => $importer)
        {
            $sheet = $spreadSheet->getSheetByName($title);
            if (! $sheet) continue;
            $importer->import($sheet);
        }
    }
}