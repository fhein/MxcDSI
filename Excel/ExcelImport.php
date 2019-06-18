<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
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
        $importer->import($sheet);
        $this->modelManager->getRepository(Product::class)->exportMappedProperties();
        return true;
    }

}