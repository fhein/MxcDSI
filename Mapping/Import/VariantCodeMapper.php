<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

class VariantCodeMapper extends BaseImportMapper implements VariantMapperInterface
{
    /**
     * Map an InnoCigs article code.
     *
     * @param Model $model
     * @param Variant $variant
     */
    public function map(Model $model, Variant $variant): void
    {
        $number = $model->getMaster();
        $variant->setNumber($this->config['variant_codes'][$number] ?? $number);
    }
}

