<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;

class ImportFlavor extends AbstractProductImport
{
    protected function cleanEntry(?string $entry)
    {
        if (! $entry || $entry === '') return null;
        $values = explode(',', $entry);
        $values = array_map('trim', $values);
        return (implode(', ', $values));
    }

    protected function processImportData()
    {
        $repository = $this->modelManager->getRepository(Product::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $repository->getFlavoredProducts();
        foreach ($this->data as $record) {
            /** @var Product $product */
            $product = $products[$record['icNumber']];
            if (! $product) continue;
            $product->setFlavor($this->cleanEntry($record['flavor']));
            $product->setContent($this->cleanEntry($record['content']));
            $product->setCapacity($this->cleanEntry($record['capacity']));
        }

        $this->modelManager->flush();
    }
}