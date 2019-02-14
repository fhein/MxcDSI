<?php

namespace MxcDropshipInnocigs\Toolbox\Arrays;

class ArrayTool
{
    /**
     * Build the cartesian product of an array of arrays
     *
     * @param array $input
     * @return array
     */
    public static function cartesianProduct(array $input)
    {
        $result = array();
        foreach ($input as $key => $values) {

            if (empty($values)) {
                continue;
            }

            if (empty($result)) {
                foreach ($values as $value) {
                    $result[] = array($key => $value);
                }
                continue;
            }
            $append = array();

            foreach ($result as &$product) {
                $product[$key] = array_shift($values);
                $copy = $product;
                foreach ($values as $item) {
                    $copy[$key] = $item;
                    $append[] = $copy;
                }
                array_unshift($values, $product[$key]);
            }

            $result = array_merge($result, $append);
        }

        return $result;
    }
}