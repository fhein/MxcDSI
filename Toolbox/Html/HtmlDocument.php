<?php

namespace MxcDropshipInnocigs\Toolbox\Html;

use DOMDocument;

class HtmlDocument
{
    /**
     * Extrahiere aus einem Html-Text alle Tabellen und liefere das Ergebnis als Array zurück.
     * Die linke Spalte der Tabelle dient als Array-Key, die anderen Spalten werden zu einem String implodiert,
     * mit MXC_DELIMITER_1 als Trenner zwischen den Zellen.
     *
     * @return array
     */
    public function getTablesAsArray(string $text)
    {
        $document = $this->getDOMDocument($text);
        $result = [];
        $tables = $document->getElementsByTagName('table');
        if ($tables->count() === 0) return $result;

        foreach ($tables as $table) {
            echo $table->textContent . "\n\n";
            $rows = $table->getElementsByTagName('tr');
            if ($rows->count() === 0) continue;
            $i = 0;
            $temp = [];
            foreach ($rows as $row) {
                $cells = $row->getElementsByTagName('td');
                foreach ($cells as $cell) {
                    $temp[$i][] = trim($cell->textContent);
                }
                $i++;
            }
            $array = [];
            foreach ($temp as $line) {
                $topic = $line[0];
                unset($line[0]);
                $line = implode('#!#', $line);
                $array[$topic] = $line;
            }
            $result[] = $array;
        }
        return $result;
    }

    protected function getDOMDocument(string $text)
    {
        $text = preg_replace('~<br/?>~', "\n", $text);
        $document = new DOMDocument();
        $document->loadHTML($text);
        return $document;
    }
}