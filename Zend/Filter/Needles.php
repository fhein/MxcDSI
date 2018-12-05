<?php

namespace MxcDropshipInnocigs\Zend\Filter;

use Traversable;
use Zend\Filter\AbstractFilter;
use Zend\Filter\Exception\InvalidArgumentException;

abstract class Needles extends IsString {

    protected $options = [
        'needles' => []
    ];

    /**
     * @param array|Traversable $options
     * @return void|AbstractFilter
     */
    public function setOptions($options) {
        parent::setOptions($options);
        $needles = $this->getOptions()['needles'];
        if (is_string($needles)) {
            $this->options['needles'] = [ $needles ];
        } elseif (! is_array($needles)|| ! $needles instanceof Traversable) {
            throw new InvalidArgumentException('Invalid argument: Option "needles" is neither string nor array nor Traversable.');
        };
        foreach ($needles as $needle) {
            if (! is_string($needle)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid argument: Expected all "needles" to be string, got %s.',
                        is_object($needle) ? get_class($needle) : gettype($needle)
                    )
                );
            }
        }
    }
}