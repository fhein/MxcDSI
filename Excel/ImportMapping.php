<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;

class ImportMapping extends AbstractProductImport
{
    protected function processImportData()
    {
        $repository = $this->modelManager->getRepository(Product::class);
        /** @noinspection PhpUndefinedMethodInspection */

        $products = $repository->getAllIndexed();
        foreach ($this->data as $record) {
            /** @var Product $product */
            $product = $products[$record['icNumber']];
            if (! $product) continue;

            $product->setCommonName($record['commonName']);
            $product->setSupplier($record['supplier']);
            $product->setBrand($record['brand']);
            $product->setType($record['type']);
            $product->setName($record['name']);
        }
        $this->modelManager->flush();
    }
}