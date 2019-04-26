<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

interface ProductMapperInterface
{
    public function map(Model $model, Product $product);
    public function report();
}