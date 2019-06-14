<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use RuntimeException;

class ExcelImport
{
    protected $importers;

    protected $excelFile = __DIR__ . '/../Config/vapee.export.xlsx';

    public function __construct(array $importers)
    {
        $this->importers = $importers;
    }

    public function import($filepath = null)
    {
        $importFile = $filepath ? $filepath : $this->excelFile;
        $spreadSheet = (new Reader())->load($importFile);
        $isFileImported = false;
        foreach ($this->importers as $title => $importer)
        {
            $sheet = $spreadSheet->getSheetByName($title);
            if (! $sheet) continue;
            $importer->import($sheet);
            $isFileImported = true;
        }
        return $isFileImported;
    }

    public function importSheet(string $sheetName, string $filepath = null)
    {
        $importer = $this->importers[$sheetName];
        if (! $importer) {
            throw new RuntimeException('Unknown sheet import');
        }

        $importFile = $filepath ? $filepath : $this->excelFile;
        $spreadSheet = (new Reader())->load($importFile);
        $sheet = $spreadSheet->getSheetByName($sheetName);
        if (! $sheet) return false;
        $importer->import($sheet);
        return true;
    }

}