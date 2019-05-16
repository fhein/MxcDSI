<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;

class CommonNameMapper extends BaseImportMapper implements ProductMapperInterface
{
    /** @var array */
    protected $report = [];

    /**
     * The common name of an article is the pure product name without
     * supplier, article group and without any other info.
     *
     * The common name gets determined here and is utilized to identify
     * related products.
     *
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product)
    {
        $name = $product->getName();
        $raw = explode(' - ', $name);
        $index = @$this->classConfig['common_name_index'][$raw[0]][$raw[1]] ?? 1;
        $name = trim($raw[$index] ?? $raw[0]);
        $replacements = ['~ \(\d+ Stück pro Packung\)~', '~Head$~'];
        $name = trim(preg_replace($replacements, '', $name));
        $product->setCommonName($name);
        $this->report[$name][] = $product->getName();
    }

    public function report()
    {
        ksort($this->report);
        (new ArrayReport())(['pmCommonName' => $this->report]);
    }

}