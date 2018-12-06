<?php
namespace MxcDropshipInnocigs\Zend\Filter;

use Traversable;

class ClassFilter extends AbstractFilter
{
    /**
     * @var array $classes
     */
    protected $classes;

    /**
     * ClassFilter constructor.
     *
     * @param string|array|Traversable $classes
     * @param int|null $onPass
     * @param int|null $onFail
     */
    public function __construct($classes, int $onPass = null, int $onFail = null) {
        parent::__construct($onPass, $onFail);
        $this->setClasses($classes);
    }

    public function validate($value) {
        foreach ($this->classes as $class) {
            if ($value instanceof $class) return $value;
        }
        throw new RuntimeException(
            sprintf('%s: Expected instance of one of classes %s, got %s',
                __METHOD__,
                implode(', ', $this->classes),
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    protected function setClasses($classes) {
        if (is_string($classes)) {
            $classes = [ $classes ];
        }
        if (! (is_array($classes) || $classes instanceof Traversable)) {
            throw new InvalidArgumentException('Invalid argument: Option "classes" is neither string nor array nor Traversable.');
        }
        foreach ($classes as $class) {
            if (is_string($class)) {
                $this->classes[] = $class;
                continue;
            }
            throw new InvalidArgumentException(
                sprintf('Invalid argument: Expected all "classes" to be string, got %s.',
                    is_object($class) ? get_class($class) : gettype($class)
                )
            );
        }
    }
}