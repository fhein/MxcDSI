<?php


namespace MxcDropshipInnocigs\Mapping\Import;


use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class ImportPiecesPerPackMapper extends BaseImportMapper implements ProductMapperInterface
{
    /**
     * If a product in general contains several pieces, i.e. not as an option,
     * the mapped product name contains a substring like (xx Stück pro Packung).
     *
     * This xx number of pieces gets derived here.
     *
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product)
    {
        $name = $product->getName();
        $matches = [];
        $ppp = 1;
        if (preg_match('~\((\d+) Stück~', $name, $matches) === 1) {
            $ppp = $matches[1];
        };
        $product->setPiecesPerPack($ppp);
    }

}