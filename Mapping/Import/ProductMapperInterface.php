<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Product;

interface ProductMapperInterface
{
    public function map(Model $model, Product $product, bool $remap = false);
    public function report();
}