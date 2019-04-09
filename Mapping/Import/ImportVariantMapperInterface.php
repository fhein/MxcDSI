<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

interface ImportVariantMapperInterface
{
    public function map(Model $model, Variant $variant);
}