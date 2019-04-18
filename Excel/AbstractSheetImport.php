<?php

namespace MxcDropshipInnocigs\Excel;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractSheetImport
{
    protected $data;

    abstract protected function processImportData();

    public function import(Worksheet $sheet)
    {
        $this->data = $this->entitiesToArray($sheet->toArray());
        if (! is_array($this->data) || empty($this->data)) return;

        $this->processImportData();
    }

    protected function entitiesToArray(array $entities)
    {
        $headers = null;
        foreach ($entities as &$entity) {
            if (! $headers) {
                $headers = $entity;
                continue;
            }
            $entity = array_combine($headers, $entity);
        }
        // remove header entity
        array_shift($entities);
        return $entities;

    }

}