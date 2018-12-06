<?php
namespace MxcDropshipInnocigs\Zend\Filter;

use Zend\Filter\Exception\RuntimeException;

class StringFilter extends AbstractFilter
{
    /**
     * StringFilter constructor.
     * @param int|null $onPass
     * @param int|null $onFail
     */
    public function __construct(int $onPass = null, int $onFail = null)
    {
        parent::__construct($onPass, $onFail);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function validate($value) {
        if (is_string($value)) return $value;
        throw new RuntimeException(
            sprintf('%s: Expected string, got %s',
                __METHOD__,
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}