<?php
namespace MxcDropshipInnocigs\Zend\Filter;

use Zend\Filter\AbstractFilter;
use Zend\Filter\Exception\RuntimeException;

class IsString extends AbstractFilter
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
        if (is_string($value)) return true;
        throw new RuntimeException(
            sprintf('Expected string, got %s',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}