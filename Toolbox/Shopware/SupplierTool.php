<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use MxcDropshipInnocigs\Models\Article;
use Shopware\Models\Article\Supplier;

class SupplierTool
{
    /**
     * If supplied $article has a supplier then get it by name from Shopware or create it if necessary.
     * Otherwise do the same with default supplier name 'unknown'
     *
     * @param Article $article
     * @return Supplier
     */
    public static function getSupplier(Article $article)
    {
        $supplierName = $article->getSupplier() ?? 'unknown';
        $modelManager = Shopware()->Models();
        $supplier = $modelManager->getRepository(Supplier::class)->findOneBy(['name' => $supplierName]);
        if (!$supplier) {
            $supplier = new Supplier();
            $modelManager->persist($supplier);
            $supplier->setName($supplierName);
        }
        return $supplier;
    }
}