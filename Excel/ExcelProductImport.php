<?php

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;

class ExcelProductImport extends ExcelImport
{
  protected $modelManager;

    public function __construct(array $importers)
    {
        parent::__construct($importers);
        $this->modelManager = Shopware()->Models();
    }

    public function import()
    {
        parent::import();
        $this->modelManager->getRepository(Product::class)->exportMappedProperties();
    }
}