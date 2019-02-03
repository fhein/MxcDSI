<?php

namespace MxcDropshipInnocigs\Import\Report\Mapper;

class SuccessiveReplacer
{
    protected $replacements;
    protected $replacer;

    public function __construct(array $params) {
        $this->replacements = $params['replacements'] ?? [];
        $this->replacer = $params['replacer'];
    }

    public function map($value) {
        if (! $this->replacer) return $value;
        $current = $value;
        $value = [];
        $value['value'] = $current;
        $value['replacements'] = [];
        $value['nomatch'] = [];
        foreach ($this->replacements as $search => $replace) {
            $new = ($this->replacer)($search, $replace, $current);
            if ($new !== $current) {
                $value['replacements'][] = [
                    'srch' => $search,
                    'repl' => $replace,
                    'rslt' => $new,
                ];
                $current = $new;
            } else {
                $value['nomatch'][] = $search;
            }
        }
        return $value;
    }
}