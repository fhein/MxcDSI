<?php

namespace MxcDropshipInnocigs\Zends\Filter;

use Zend\Filter\AbstractFilter;
use Zend\Filter\Exception\RuntimeException;

class ClassFilter extends AbstractFilter
{
    /**
     * @var string $class
     */
    protected $class;

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $object
     * @throws RuntimeException If filtering $value is impossible
     * @return mixed
     */
    public function filter($object)
    {
        if ($object instanceof $this->class) return $object;
        throw new RuntimeException(
            sprintf('Expected instance of %s, got %s',
                $this->class,
                is_object($object) ? get_class($object) : gettype($object)
            )
        );
    }
}