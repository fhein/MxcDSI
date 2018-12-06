<?php

namespace MxcDropshipInnocigs\Zend\Filter;

class StringReplace extends StringFilter
{
    /**
     * @var string $search
     */
    protected $search;
    /**
     * @var string $replace
     */
    protected $replace;
    /**
     * @var string $method
     */
    protected $method;

    public function __construct(string $search, string $replace, bool $ignoreCase = false) {
        parent::__construct(AbstractFilter::RETURN_VALUE, AbstractFilter::RETURN_VALUE);
        $this->search = $search;
        $this->replace = $replace;
        $this->method = $ignoreCase ? 'str_ireplace' : 'str_replace';
    }

    public function apply(&$value) {
        ($this->method)($this->search, $this->replace, $value);
        return true;
    }
}