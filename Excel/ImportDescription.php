<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Excel;

use MxcDropshipInnocigs\Models\Product;
use Shopware\Models\Article\Article;

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
            $description = $record['description'];

            $product->setDescription($description);

            /** @var Article $article */
            $article = $product->getArticle();
            if (! $article) continue;
            $article->setDescriptionLong($description);
        }
        $this->modelManager->flush();
        $repository->exportMappedProperties();
    }
}