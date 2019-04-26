<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class ProductNumberMapper extends BaseImportMapper implements ProductMapperInterface
{
    /**
     * Map an InnoCigs article code.
     *
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product): void
    {
        $number = $model->getMaster();
        $product->setNumber($this->classConfig['product_number'][$number] ?? $number);
    }

    public function report()
    {
        // add reporting here
    }
}

