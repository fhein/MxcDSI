<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;

class ProductMappings implements AugmentedObject
{
    use ClassConfigAwareTrait;

    public function getConfig()
    {
        return $this->classConfig ?? [];
    }
}