<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Shopware\Components\Model\ModelManager;

class ExportNewProducts extends AbstractProductExport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $products;

    public function setSheetData()
    {
        $this->sortColumns($this->data);

        $headers[] = array_keys($this->data[0]);
        $data = array_merge($headers, $this->data);
        $this->sheet->fromArray($data);
    }

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->sheet->getHighestRowAndColumn();
        $bColumn = $this->sheet->getColumnDimension('B');
        $bColumn->setAutoSize(false);
        $bColumn->setWidth(15);
        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
    }

    protected function loadRawExportData(): ?array
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products
            ?? $this->products = $this->modelManager->getRepository(Product::class)->getExcelExportNewProducts();
    }
}