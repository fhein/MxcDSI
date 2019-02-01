<?php

namespace MxcDropshipInnocigs\Import\Report;

use MxcDropshipInnocigs\Import\Report\Filter\ContainsOneOf;
use Zend\Config\Factory;

class MappingReport
{
    protected $reportDir = __DIR__ . '/../../Dump';

    protected $fileNames = [
        'mismatchedOptionNames' => 'option.name.mismatches.php',
        'nameWithoutOptionsMap' => 'article.name.without.options.php',
        'categoryMap'           => 'map.category.php',
        'names'                 => 'article.name.php',
        'nameMap'               => 'map.article.name.php',
        'suppliers'             => 'suppliers.php',
        'brands'                => 'brands.php',
        'usedStrReplacements'   => 'replace.string.used.php',
        'usedPregReplacements'  => 'replace.preg.used.php',
    ];

    protected $filters = [
        'names'   => [
            ContainsOneOf::class => [
                'InnoCigs',
                'SC'
            ]
        ],
    ];

    public function report(array $topic) {

        $topic['names'] = array_values($topic['nameMap']);
        sort($topic['names']);
        ksort($topic['nameMap']);
        ksort($topic['brands']);
        ksort($topic['suppliers']);
        $topic['suppliers'] = array_keys($topic['suppliers']);
        $topic['brands'] = array_keys($topic['brands']);

        $map = [];
        foreach ($topic['nameMap'] as $key => $value) {
            $map[] = [
                'old' => $key,
                'wop' => $topic['nameWithoutOptionsMap'][$key],
                'new' => $value,
            ];
        }
        $topic['nameMap'] = $map;

        foreach (array_keys($topic) as $what) {
            $this->process($what, $topic[$what]);
        }
    }

    public function process(?string $what, ?array $topic) {
        if (! $what || ! $topic || empty($topic)) return;
        $fn = $this->fileNames[$what];
        if (! $fn) return;

        $dir = $this->reportDir . '/';
        $fn = $this->fileNames[$what];
        $file = $dir . $fn;
        $diffFile = $dir . '_diff.' . $fn;

        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            $old = include $file;
            $diff = array_diff($topic, $old);
            Factory::toFile($diffFile, $diff);
        }
        Factory::toFile($file, $topic);
        $filters = $this->filters[$what];
        if (! $filters) return;
        foreach ($filters as $filter => $params) {
            $f = new $filter($params);
            $result = array_filter($topic, [$f, 'filter']);
            $ffn = $dir . '_filter.' . '.' . $fn;
            Factory::toFile($ffn, $result);
        }
    }
}