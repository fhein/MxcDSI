<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcDropshipInnocigs\Excel;


use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

abstract class AbstractProductExport extends AbstractSheetExport implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    private $fixedColumns = [
        'icNumber',
        'type',
        'supplier',
        'brand',
        'name',
    ];

    protected $columnSort = ['type', 'supplier', 'brand', 'name'];

    protected function registerColumns()
    {
        foreach ($this->fixedColumns as $key => $name) {
            $this->registerColumn($name);
        }
    }

    protected function formatSheet(): void
    {
        foreach (range('A', 'D') as $col) {
            $this->sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $this->sheet->getColumnDimension('E')->setWidth(80);
        $highest = $this->getHighestRowAndColumn();
        $range = $this->getRange(['A', 1, $highest['column'], $highest['row']]);

        $alignment = $this->sheet->getStyle($range)->getAlignment();

        $alignment->setVertical(Alignment::VERTICAL_TOP);
        $alignment->setWrapText(true);

        $this->sheet->freezePane('A2');
    }
}