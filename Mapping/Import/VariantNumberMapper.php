<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Variant;

class VariantNumberMapper extends BaseImportMapper implements VariantMapperInterface
{
    /**
     * Map an InnoCigs article code.
     *
     * @param Model $model
     * @param Variant $variant
     */
    public function map(Model $model, Variant $variant): void
    {
        $number = $model->getModel();
        $variant->setNumber($this->classConfig['variant_codes'][$number] ?? $number);
    }
}

