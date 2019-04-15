<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

interface VariantMapperInterface
{
    public function map(Model $model, Variant $variant);
}