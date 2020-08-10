<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;

class BaseImportMapper implements LoggerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ClassConfigAwareTrait;
}