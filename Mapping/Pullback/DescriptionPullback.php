<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping\Pullback;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Shopware\Models\Article\Article;

class DescriptionPullback implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    private $spellChecker;

    public function __construct()
    {
        $this->spellChecker = MxcDropshipIntegrator::getServices()->get(SpellChecker::class);
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