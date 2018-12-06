<?php

namespace MxcDropshipInnocigs\Zend\Filter;

use Traversable;
use Zend\Filter\AbstractFilter;
use Zend\Filter\Exception\InvalidArgumentException;

abstract class Needles extends StringFilter {

    /**
     * @var array $needles
     */
    protected $needles = [];

    /**
     * Needles constructor.
     * @param $needles
     * @param int|null $onPass
     * @param int|null $onFail
     */
    public function __construct($needles, int $onPass = null, int $onFail = null) {
        parent::__construct($onPass, $onFail);
        $this->setNeedles($needles);
    }
    /**
     * @param array|Traversable $needles
     * @return void|AbstractFilter
     */
    public function setNeedles($needles) {
        if (is_string($needles)) {
            $needles = [ $needles ];
        }
        if (! (is_array($needles) || $needles instanceof Traversable)) {
            throw new InvalidArgumentException('Invalid argument: Option "needles" is neither string nor array nor Traversable.');
        }
        foreach ($needles as $needle) {
            if (is_string($needle)) {
                $this->needles[] = $needle;
                continue;
            }
            throw new InvalidArgumentException(
                sprintf('Invalid argument: Expected all "needles" to be string, got %s.',
                    is_object($needle) ? get_class($needle) : gettype($needle)
                )
            );
        }
    }
}