<?php


namespace MxcDropshipInnocigs\Excel;


use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;

abstract class AbstractProductExport extends AbstractSheetExport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    private $fixedColumns = [
        'icNumber',
        'type',
        'supplier',
        'brand',
        'name',
        'EK Netto',
        'EK Brutto',
        'UVP Brutto',
        'Marge UVP',
    ];

    protected $columnSort = ['type', 'supplier', 'brand', 'name'];

    public function __construct(
        ModelManager $modelManager,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

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

        foreach (range('F', $highest['column']) as $col) {
            $this->sheet->getColumnDimension($col)->setWidth(16);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->freezePane('A2');
    }
}