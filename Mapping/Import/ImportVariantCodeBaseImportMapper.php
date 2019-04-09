<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

class ImportVariantCodeBaseImportMapper extends BaseImportMapper implements ImportVariantMapperInterface
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
        $variant->setNumber($this->config['variant_codes'][$number] ?? $number);
    }
}

