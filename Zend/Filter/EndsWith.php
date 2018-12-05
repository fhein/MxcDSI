<?php

namespace MxcDropshipInnocigs\Zend\Filter;

use Zend\Filter\Exception;

class EndsWith extends Needles
{
    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Exception\RuntimeException If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        if (! is_string($value)) return $value;
        $needles = $this->getOptions()['needles'];

        foreach($needles as $needle) {
            $len = strlen($needle);
            if (substr($value, -$len, $len) === $needle) return true;
        }
        return false;
    }
}