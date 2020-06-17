<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping\Pullback;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Models\Article\Article;

class DescriptionPullback implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    private $spellChecker;

    public function __construct()
    {
        $this->spellChecker = MxcDropshipInnocigs::getServices()->get(SpellChecker::class);
    }

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
            $description = $this->spellChecker->check($description);
            $article->setDescriptionLong($description);
            $product->setDescription($description);
        }
        $this->modelManager->flush();
    }
}