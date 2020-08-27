<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;

class BaseImportMapper implements AugmentedObject
{
    use LoggerAwareTrait;
    use ClassConfigAwareTrait;
}