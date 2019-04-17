<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use Shopware\Components\Model\ModelManager;

class ExportFlavor extends AbstractProductExport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $products;

    public function setSheetData(array $products)
    {
        if (! $products) return;

        usort($products, [$this, 'compare']);
        $headers[] = array_keys($products[0]);
        $data = array_merge($headers, $products);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->fromArray($data);
    }

    protected function formatSheet(): void
    {
        parent::formatSheet();
        $highest = $this->sheet->getHighestRowAndColumn();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->getStyle('F2:'. $highest['column'] . $highest['row'])
            ->getNumberFormat()->setFormatCode('@');
        $this->alternateRowColors();
    }

    protected function loadRawExportData(): ?array
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->modelManager->getRepository(Product::class)->getExportFlavoredProducts();
    }
}