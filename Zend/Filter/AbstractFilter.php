<?php

namespace MxcDropshipInnocigs\Zend\Filter;

use Zend\Filter\Exception\RuntimeException;
use Zend\Filter\FilterInterface;

class AbstractFilter implements FilterInterface
{
    const RETURN_BOOL = 0;
    const RETURN_VALUE = 1;
    const RETURN_NULL = 2;
    const THROW = 3;

    protected $onPass;
    protected $onFail;

    public function __construct(int $onPass = null, $onFail = null)
    {
        $this->onPass = $onPass ?? self::RETURN_VALUE;
        $this->onFail = $onFail ?? self::RETURN_NULL;
    }

    /**
     * @param mixed $value
     * @return bool|mixed
     */
    public function filter($value) {
        return $this->returnResult($this->validate($value), $this->apply($value));
    }

    protected function apply(/** @noinspection PhpUnusedParameterInspection */ &$value) {
        return true;
    }

    protected function validate($value) {
        return $value;
    }

    protected function returnResult($value, bool $result) {
        $action = $result ? $this->onPass : $this->onFail;
        switch ($action) {
            case self::RETURN_BOOL:
                return $result;
            case self::RETURN_VALUE:
                return $value;
            case self::THROW:
                $b = $result ? 'true' : 'false';
                throw new RuntimeException(
                    sprintf('Unexpected filter result %s for %s.',
                        $b,
                        is_object($value) ? get_class($value) : gettype($value)
                    )
                );
            default:
                return $result;
        }
    }

    public function __invoke($value) {
        return $this->filter($value);
    }
}
