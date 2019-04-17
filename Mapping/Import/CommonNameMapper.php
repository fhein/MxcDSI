<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class CommonNameMapper extends BaseImportMapper implements ProductMapperInterface
{

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
        $index = @$this->config['common_name_index'][$raw[0]][$raw[1]] ?? 1;
        $name = trim($raw[$index] ?? $raw[0]);
        $replacements = ['~ \(\d+ Stück pro Packung\)~', '~Head$~'];
        $name = preg_replace($replacements, '', $name);
        $product->setCommonName(trim($name));
    }
}