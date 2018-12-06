<?php

namespace MxcDropshipInnocigs\Mapping\Filter;

use MxcDropshipInnocigs\Models\InnocigsModelInterface;
use MxcDropshipInnocigs\Zend\Filter\AbstractFilter;
use MxcDropshipInnocigs\Zend\Filter\ClassFilter;

/**
 * Class InnocigsModel
 *
 * Filter which accepts classes implementing InnocigsModelInterface for filtering
 *
 * @package MxcDropshipInnocigs\Mapping\Filter
 */
class InnocigsModel extends ClassFilter
{
    public function __construct() {
        parent::__construct(
            InnocigsModelInterface::class,
            AbstractFilter::RETURN_VALUE,
            AbstractFilter::RETURN_VALUE
        );
    }
}