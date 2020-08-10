<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Toolbox\Shopware\DatabaseTool;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use RuntimeException;

class ExcelImport implements ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    protected $importers;

    protected $excelFile = __DIR__ . '/../Config/vapee.export.xlsx';

    public function __construct(array $importers)
    {
        $this->importers = $importers;
    }

    public function import($filepath = null)
    {
        DatabaseTool::removeOrphanedDetails();
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
        if ($isFileImported) {
            $this->modelManager->getRepository(Product::class)->exportMappedProperties();
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
        $data = $sheet->toArray();
        if (! is_array($data) || empty($data)) return false;
        $data = $importer->entitiesToArray($data);
        if (! is_array($data) || empty($data)) return false;

        // free memory used by PHPSpreadsheet
        $spreadSheet->disconnectWorksheets();
        unset($spreadSheet);
        $importer->processImportData($data);
        $this->modelManager->getRepository(Product::class)->exportMappedProperties();
        return true;
    }

}