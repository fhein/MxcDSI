<?php

namespace MxcDropshipInnocigs\Import\Report\Filter;

class ContainsAllOf
{
    protected $matches;

    public function __construct(array $matches) {
        $this->matches = $matches;
    }

    public function filter($value) {
        foreach ($this->matches as $match) {
            if (strpos($value, $match) === false) return false;
        }
        return true;
    }
}