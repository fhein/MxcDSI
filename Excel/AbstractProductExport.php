<?php


namespace MxcDropshipInnocigs\Excel;


abstract class AbstractProductExport extends AbstractSheetExport
{

    /**
     * Callback for usort
     *
     * @param $one
     * @param $two
     * @return bool
     */
    protected function compare($one, $two)
    {
        $t1 = $one['type'];
        $t2 = $two['type'];
        if ($t1 > $t2) {
            return true;
        }
        if ($t1 === $t2) {
            $s1 = $one['supplier'];
            $s2 = $two['supplier'];
            if ($s1 > $s2) {
                return true;
            }
            if ($s1 === $s2) {
                return $one['brand'] > $two['brand'];
            }
        }
        return false;
    }

}