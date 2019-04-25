<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;

class ImportMappings implements ClassConfigAwareInterface
{
    use ClassConfigAwareTrait;

    public function getClassConfig()
    {
        return $this->classConfig ?? [];
    }
}