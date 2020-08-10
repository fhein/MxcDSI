<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\Product;
use Shopware\Models\Article\Article;

class AssociatedArticlesMapper implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /**
     * Set the related articles of a Shopware article according to the settings of the InnoCigs product.
     * If the $replace flag is true, the related articles of the Shopware article will be replaced. If the
     * $replace flag is false, new related articles will be added, if any.
     *
     * @param Product $product
     * @param bool $replace true: replace related articles, false: add related articles
     */
    public function setRelatedArticles(Product $product, bool $replace = false)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (!$article) return;

        $related = $article->getRelated();
        if ($replace) $related->clear();

        $relatedArticles = $this->getArticles($product->getRelatedProducts());
        $this->addArticlesToCollection($relatedArticles, $related);
    }

    /**
     * Set the similar articles of a Shopware article according to the settings of the InnoCigs product.
     * If the $replace flag is true, the similar articles of the Shopware article will be replaced. If the
     * $replace flag is false, new similar articles will be added, if any.
     *
     * @param Product $product
     * @param bool $replace true: replace related articles, false: add related articles
     */
    public function setSimilarArticles(Product $product, bool $replace = false)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (!$article) return;

        $similar = $article->getSimilar();
        if ($replace) $similar->clear();

        $similarArticles = $this->getArticles($product->getSimilarProducts());
        $this->addArticlesToCollection($similarArticles, $similar);
    }

    /**
     * Add all articles of the given Shopware article collection to the target collection.
     * No duplicates.
     *
     * @param Collection $articles
     * @param Collection $collection
     */
    protected function addArticlesToCollection(Collection $articles, Collection $collection)
    {
        foreach ($articles as $article) {
            if (! $collection->contains($article)) {
                $collection->add($article);
            }
        }
    }

    /**
     * For a given collection of InnoCigs articles return a collection of all associated Shopware articles.
     *
     * @param Collection $products
     * @return ArrayCollection
     */
    protected function getArticles(Collection $products): ArrayCollection
    {
        $articles = [];
        foreach ($products as $product) {
            $article = $product->getArticle();
            if ($article !== null) {
                $articles[] = $article;
            }
        }
        return new ArrayCollection($articles);
    }

    /**
     * Update the related and similar article lists of all Shopware articles
     * where the corresponding product has related and similar articles from
     * the given $products array.
     *
     * @param array $productIds
     */
    public function updateArticleLinks(array $productIds)
    {
        if (empty($productIds)) return;

        $repository = $this->modelManager->getRepository(Product::class);

        $productsWithRelatedNewArticles = $repository->getProductsHavingRelatedArticles($productIds);
        foreach ($productsWithRelatedNewArticles as $product) {
            $this->setRelatedArticles($product);
        }

        $productsWithSimilarNewArticles = $repository->getProductsHavingSimilarArticles($productIds);
        foreach ($productsWithSimilarNewArticles as $product) {
            $this->setSimilarArticles($product);
        }
    }

    public function setAssociatedArticles(array $productIds) {
        if (empty($productIds)) return;

        $repository = $this->modelManager->getRepository(Product::class);
        $products = $repository->getProductsByIds($productIds);
        foreach ($products as $product) {
            $this->setRelatedArticles($product, false);
            $this->setSimilarArticles($product, false);
        }
    }
}