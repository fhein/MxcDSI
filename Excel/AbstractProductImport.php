<?php

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;

abstract class AbstractProductImport extends AbstractSheetImport implements AugmentedObject
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
}