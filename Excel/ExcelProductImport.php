<?php

namespace MxcDropshipIntegrator\Excel;

use MxcDropshipIntegrator\Models\Product;

class ExcelProductImport extends ExcelImport
{
  protected $modelManager;

    public function __construct(array $importers)
    {
        parent::__construct($importers);
        $this->modelManager = Shopware()->Models();
    }

    public function import($filepath = null)
    {
        $result = parent::import($filepath);
        $this->modelManager->getRepository(Product::class)->exportMappedProperties();
        return $result;
    }
}