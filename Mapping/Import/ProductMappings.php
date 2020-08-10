<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;

class ProductMappings implements ClassConfigAwareInterface
{
    use ClassConfigAwareTrait;

    public function getConfig()
    {
        return $this->classConfig ?? [];
    }
}