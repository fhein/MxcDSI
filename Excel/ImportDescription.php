<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;

class ImportDescription extends AbstractProductImport
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

            $product->setDescription($record['description']);
        }
        $this->modelManager->flush();
        $repository->exportMappedProperties();
    }
}