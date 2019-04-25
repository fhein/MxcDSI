<?php

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;

class ImportDescription extends AbstractProductImport
{
    protected function processImportData()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
        foreach ($this->data as $record) {
            /** @var Product $product */
            $product = $products[$record['icNumber']];
            if (! $product) continue;

            $product->setDescription($record['description']);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }
}