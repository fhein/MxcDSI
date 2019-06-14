<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use Shopware\Components\Model\ModelManager;

class ExportDescription extends AbstractProductExport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $products;

    protected function registerColumns()
    {
        parent::registerColumns();
        $this->registerColumn('description');
        $this->registerColumn('icDescription');
    }

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

        $this->sheet->getStyle('F2:'. $highest['column'] . $highest['row'])
            ->getNumberFormat()->setFormatCode('@');

        $this->sheet->getColumnDimension('F')->setWidth(150);
        $this->sheet->getColumnDimension('G')->setWidth(150);

        $this->setAlternateRowColors();
        $this->formatHeaderLine();
        $this->setBorders('allBorders', Border::BORDER_THIN, 'FFBFBFBF');
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000');
        $range = $this->getRange(['F', 1, 'F', $highest['row']]);
        $this->setBorders('outline', Border::BORDER_MEDIUM, 'FF000000', $range);
        $this->setConditionalStyles();
    }

    protected function setConditionalStyles(){
        $this->setConditionalFormat('description',
            Conditional::CONDITION_CELLIS,
            Conditional::OPERATOR_EQUAL,
            'icDescription',
            '	C5D9F1');//light blue
    }

    protected function loadRawExportData(): ?array
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products
            ?? $this->products = $this->modelManager->getRepository(Product::class)->getExcelExportDescription();
    }
}