<?php

namespace MxcDropshipInnocigs\Toolbox\Csv;

class CsvTool
{
    /**
     * Import csv content to an associative array
     * The first line of the csv file should contain column headers
     * which are used as array keys.
     *
     * @param string $content
     * @param string $delimiter
     *
     * @return array
     */
    public function import(string $content, string $delimiter)
    {
        $entities = str_getcsv(html_entity_decode($content), "\n");

        $headers = null;
        foreach ($entities as &$entity) {
            $entity = str_getcsv($entity, $delimiter);
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

    function arrayToCsv(array $array, string $delimiter)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)), $delimiter);
        foreach ($array as $row) {
            fputcsv($df, $row, $delimiter);
        }
        fclose($df);
        return ob_get_clean();
    }

    public function export(string $filename, array $array, string $delimiter = ';') {
        $content = $this->arrayToCsv($array, $delimiter);
        file_put_contents($filename, $content);
    }
}