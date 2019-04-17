<?php


namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Shopware\Components\Model\ModelManager;

abstract class AbstractSheetExport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var Worksheet */
    protected $sheet;

    abstract protected function setSheetData(array $data);
    abstract protected function loadRawExportData(): ?array;

    public function __construct(
        ModelManager $modelManager,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

    public function export(Worksheet $sheet)
    {
        $this->sheet = $sheet;
        $data = $this->loadRawExportData();
        $this->setSheetData($data);
        $this->formatSheet();
    }

    protected function alternateRowColors(bool $excludeHeader = true, $color1 = 'FFF0F0F0', $color2 = null)
    {
        $highest = $this->sheet->getHighestRowAndColumn();
        $startLine = $excludeHeader ? 1 : 0;
        for ($i = $startLine; $i <= $highest['row']; $i++) {
            $color = ($i % 2 === 0) ? $color1 : $color2;

            if (! $color) continue;
            $range = 'A' . $i . ':'. $highest['column'] . $i;
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->sheet->getStyle($range)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => array('argb' => 'FFF3F3F3')
                    ]]
            );
        }
    }

    protected function formatSheet(): void
    {
        foreach (range('A', 'D') as $col) {
            $this->sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $this->sheet->getColumnDimension('E')->setWidth(80);
        $highest = $this->sheet->getHighestRowAndColumn();

        foreach (range('F', $highest['column']) as $col) {
            $this->sheet->getColumnDimension($col)->setWidth(16);

        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->freezePane('A2');
    }


}