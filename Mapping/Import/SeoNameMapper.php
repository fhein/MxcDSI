<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class SeoNameMapper extends BaseImportMapper implements ProductMapperInterface
{
    /** @var array */
    protected $report = [];

    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $name = $product->getName();
        if (is_int(strpos($name, '/'))) {
            $seoName = str_replace('/', '-', $name);
            $product->setSeoName($seoName);
        }
    }

    public function report()
    {
    }

}