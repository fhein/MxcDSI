<?php


namespace MxcDropshipInnocigs\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractSheetExport
{
    /** @var Worksheet */
    protected $sheet;

    /** @var array */
    protected $highest;

    /** @var array */
    protected $columns;

    /** @var array */
    protected $columnSort = null;

    /** @var array */
    protected $data;

    abstract protected function setSheetData();
    abstract protected function loadRawExportData(): ?array;
    abstract protected function formatSheet();
    abstract protected function registerColumns();

    public function export(Worksheet $sheet)
    {
        $this->sheet = $sheet;
        $this->data = $this->loadRawExportData();
        if (! $this->data) return false;
        $this->registerColumns();
        $this->setSheetData();
        $this->formatSheet();
        return true;
    }

    protected function setAlternateRowColors(bool $excludeHeader = true, $color1 = 'FFF0F0F0', $color2 = null)
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
    protected function formatHeaderLine(string $color = 'FFBFBFBF')
    {
        $highest = $this->getHighestRowAndColumn();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->getStyle('A1:'. $highest['column']. '1')->applyFromArray(
            [
                'fill'    => [
                    'fillType'  => Fill::FILL_SOLID,
                    'color' => ['argb' => $color]
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ]
            ]
        );
    }

    protected function setBorders($which, $style, $color, $range = null) {
        $highest = $this->getHighestRowAndColumn();
        $range = $range ?? $this->getRange(['A', 1, $highest['column'], $highest['row']]);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sheet->getStyle($range)->applyFromArray(
            [
                'borders' => [
                    $which => [
                        'borderStyle' => $style,
                        'color' => ['argb' => $color]],
                ]
            ]
        );
    }

    protected function getHighestRowAndColumn() {
        return $this->highest ?? $this->highest = $this->sheet->getHighestRowAndColumn();
    }

    protected function getRange(array $a)
    {
        $range = $a[0] . $a[1];
        $end = isset($a[2]) && isset($a[3]) ? $a[2] . $a[3] : null;
        if ($end) $range .= ':' . $end;
        return $range;
    }

    protected function getColumn(string $name)
    {
        return $this->columns[$name] ?? null;
    }

    protected function registerColumn(string $column, int $idx = null)
    {
        $idx = $idx ?? count($this->columns);
        $this->columns[$column] = Coordinate::stringFromColumnIndex($idx+1);
    }

    protected function getColumns()
    {
        return $this->columns;
    }

    protected function sortColumns(array &$data)
    {
        if ($data !== null && $this->columnSort !== null) {
            usort($data, [$this, 'compare']);
        }
    }

    private function compare($one, $two)
    {
        foreach ($this->columnSort as $idx) {
            if ($one[$idx] > $two[$idx]) return true;
            if ($one[$idx] < $two[$idx]) return false;
        }
        return false;
    }


}