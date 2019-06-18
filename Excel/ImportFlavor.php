<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;

class ImportFlavor extends AbstractProductImport
{
    protected function processImportData()
    {
        $repository = $this->modelManager->getRepository(Product::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $repository->getFlavoredProducts();
        foreach ($this->data as $record) {
            /** @var Product $product */
            $product = $products[$record['icNumber']];
            if (! $product) continue;

            $values = explode(',', $record['flavor']);
            $values = array_map('trim', $values);
            $flavor = implode(', ', $values);
            $product->setFlavor($flavor);
        }

        $this->modelManager->flush();
    }
}