<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;

class BaseImportMapper implements LoggerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ClassConfigAwareTrait;
}