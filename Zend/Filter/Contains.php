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
    public function filter($value)
    {
        if (! is_string($value)) {
            throw new RuntimeException(
                sprintf('Expected string, got %s',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        };

        $needles = $this->getOptions()['needles'];

        foreach($needles as $needle) {
            if (strpos($value, $needle) !== false) return true;
        }

        return false;
    }
}