<?php

namespace MxcDropshipIntegrator\Excel;

abstract class AbstractSheetImport
{
    protected $data;

    abstract public function processImportData(array &$data);

//    public function import(Worksheet $sheet)
//    {
//        $this->data = $this->entitiesToArray($sheet->toArray());
//        if (! is_array($this->data) || empty($this->data)) return;
//
//        $this->processImportData();
//    }

    public function entitiesToArray(array $entities)
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