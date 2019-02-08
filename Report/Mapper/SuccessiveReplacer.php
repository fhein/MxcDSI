<?php

namespace MxcDropshipInnocigs\Report\Mapper;

class SuccessiveReplacer
{
    protected $replacements;
    protected $replacer;
    protected $showNotMatched;

    public function __construct(array $params, bool $showNotMatched = false) {
        $this->replacements = $params['replacements'] ?? [];
        $this->replacer = $params['replacer'];
        $this->showNotMatched = $showNotMatched;
    }

    public function map($value) {
        if (! $this->replacer) return $value;
        $current = $value;
        $value = [];
        $value['value'] = $current;
        $value['replacements'] = [];
        if ($this->showNotMatched) $value['nomatch'] = [];
        foreach ($this->replacements as $search => $replace) {
            $new = ($this->replacer)($search, $replace, $current);
            if ($new !== $current) {
                $value['replacements'][] = [
                    'srch' => $search,
                    'repl' => $replace,
                    'rslt' => $new,
                ];
                $current = $new;
            } elseif ($this->showNotMatched) {
                $value['nomatch'][] = $search;
            }
        }
        return $value;
    }
}