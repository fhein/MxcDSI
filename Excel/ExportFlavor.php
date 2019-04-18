<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Shopware\Components\Model\ModelManager;

class ExportFlavor extends AbstractProductExport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    public function setSheetData()
    {
        $products = $this->data;
        usort($products, [$this, 'compare']);
        $headers[] = array_keys($products[0]);
        $data = array_merge($headers, $products);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->fromArray($data);
    }

    protected function registerColumns()
    {
        parent::registerColumns();
        $this->registerColumn('flavor');
    }

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->getHighestRowAndColumn();
        /** @noinspection PhpUnhandledExceptionInspection */
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
        return $this->modelManager->getRepository(Product::class)->getExportFlavoredProducts();
    }
}