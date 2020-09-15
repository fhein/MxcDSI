<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Variant;

interface VariantMapperInterface
{
    public function map(Model $model, Variant $variant);
}