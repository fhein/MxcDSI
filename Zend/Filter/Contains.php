<?php

namespace MxcDropshipInnocigs\Zend\Filter;

use Zend\Filter\Exception\RuntimeException;

class Contains extends Needles
{
    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws RuntimeException If filtering $value is impossible
     * @return mixed
     */
    public function apply(&$value)
    {
        if ($needle = '') return true;
        foreach($this->needles as $needle) {
            if (strpos($value, $needle) !== false) return true;
        }
        return false;
    }
}