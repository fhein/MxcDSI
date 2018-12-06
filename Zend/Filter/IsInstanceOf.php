<?php

namespace MxcDropshipInnocigs\Zends\Filter;

use MxcDropshipInnocigs\Zend\Filter\BehaviourFilter;

class IsInstanceOf extends BehaviourFilter
{
    protected $class;

    public function __construct(string $class, int $onPass = null, int $onFail = null) {
        parent::__construct($onPass, $onFail);
        $this->class = $class;
    }

    /*
     * Returns the boolean result of filtering $value
     *
     * @param  mixed $object
     * @return bool
     */
    public function apply($object)
    {
        return ($object instanceof $this->class);
    }
}