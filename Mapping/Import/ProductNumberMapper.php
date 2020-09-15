<?php


namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Product;

class ProductNumberMapper extends BaseImportMapper implements ProductMapperInterface
{
    /**
     * Map an InnoCigs article code.
     *
     * @param Model $model
     * @param Product $product
     * @param bool $remap
     */
    public function map(Model $model, Product $product, bool $remap = false): void
    {
        $number = $model->getMaster();
        $product->setNumber($this->classConfig['product_number'][$number] ?? $number);
    }

    public function report()
    {
        // add reporting here
    }
}

