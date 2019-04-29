<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;

class ProductMappings implements ClassConfigAwareInterface
{
    use ClassConfigAwareTrait;

    public function getConfig()
    {
        return $this->classConfig ?? [];
    }
}