<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Description;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use Shopware\Models\Article\Article;

class DescriptionExport implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    public function pullDescriptions(array $products = null)
    {
        $products = $products ?? $this->modelManager->getRepository(Product::class)->findAll();
        /** @var Product $product */
        foreach ($products as $product)
        {
            /** @var Article $article */
            $article = $product->getArticle();
            if (! $article) continue;
            $description = $article->getDescriptionLong();
            $product->setDescription($description);
        }
        $this->modelManager->flush();
        $this->modelManager->getRepository(Product::class)->exportMappedProperties();
    }
}