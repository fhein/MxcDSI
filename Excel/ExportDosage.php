<?php

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Shopware\Components\Model\ModelManager;

class ExportDosage
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $products;

    public function __construct(
        ModelManager $modelManager,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

    public function export(Worksheet $sheet)
    {
        $data = $this->getExportData();

        /** @noinspection PhpUnhandledExceptionInspection */
        $sheet->fromArray($data);
        $this->formatSheet($sheet);
    }

    public function getExportData()
    {
        $products = $this->getProducts();
        usort($products, [$this, 'compare']);
        $headers[] = array_keys($products[0]);
        $data = array_merge($headers, $products);
        return $data;
    }

    /**
     * @param Worksheet $sheet
     */
    protected function formatSheet(Worksheet $sheet): void
    {
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('E')->setWidth(80);
        $highest = $sheet->getHighestRowAndColumn();

        foreach (range('F', $highest['column']) as $col) {
            $sheet->getColumnDimension($col)->setWidth(16);

        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $sheet->freezePane('A2');

        /** @noinspection PhpUnhandledExceptionInspection */
        $sheet->getStyle('F2:'. $highest['column'] . $highest['row'])
            ->getNumberFormat()->setFormatCode('@');

    }

    protected function getProducts()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products
            ?? $this->products = $this->modelManager->getRepository(Product::class)->getAromaExcelExport();
    }

    /**
     * Callback for usort
     *
     * @param $one
     * @param $two
     * @return bool
     */
    protected function compare($one, $two)
    {
        $t1 = $one['type'];
        $t2 = $two['type'];
        if ($t1 > $t2) {
            return true;
        }
        if ($t1 === $t2) {
            $s1 = $one['supplier'];
            $s2 = $two['supplier'];
            if ($s1 > $s2) {
                return true;
            }
            if ($s1 === $s2) {
                return $one['brand'] > $two['brand'];
            }
        }
        return false;
    }
}