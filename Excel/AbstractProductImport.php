<?php

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;

abstract class AbstractProductImport extends AbstractSheetImport implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
}