<?php


namespace MxcDropshipInnocigs\Mapping\Shopware;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use Shopware\Models\Article\Article;

class AssociatedArticlesMapper implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /** @var array */
    protected $associatedProducts;

    /**
     * @param array $activeProducts
     * @return array
     */
    public function getAssociatedProducts(array $activeProducts): array
    {
        $this->associatedProducts = [];
        foreach ($activeProducts as $product) {
            $this->prepareAssociatedProducts($product);
        }
        return $this->associatedProducts;
    }

    /**
     * Fill the $this->associatedArticles recursively
     *
     * @param Product $product
     */
    public function prepareAssociatedProducts(Product $product)
    {
        // exit recursion if $product is registered already
        if ($this->associatedProducts[$product->getIcNumber()]) {
            return;
        }

        $this->prepareAssociatedProductsCollection(
            $product->getRelatedProducts(),
            $product->getCreateRelatedProducts(),
            $product->getActivateCreatedRelatedProducts()
        );

        $this->prepareAssociatedProductsCollection(
            $product->getSimilarProducts(),
            $product->getCreateSimilarProducts(),
            $product->getActivateCreatedSimilarProducts()
        );
    }

    /**
     * @param Collection $products
     * @param bool $createAssociated
     * @param bool $activateAssociated
     */
    public function prepareAssociatedProductsCollection(
        Collection $products,
        bool $createAssociated,
        bool $activateAssociated
    ) {
        /** @var Product $product */
        foreach ($products as $product) {
            $isNew = $product->getArticle() === null;
            if (!$createAssociated && $isNew) {
                continue;
            }
            if ($isNew) {
                $product->setActive($activateAssociated);
            }
            $this->associatedProducts[$product->getIcNumber()] = $product;

            // Recursion
            $this->prepareAssociatedProducts($product);
        }
    }

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
        if ($replace) {
            $related->clear();
        }
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
        if ($replace) {
            $similar->clear();
        }
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
    public function addArticlesToCollection(Collection $articles, Collection $collection)
    {
        foreach ($articles as $article) {
            if (!$collection->contains($article)) {
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
    public function getArticles(Collection $products): ArrayCollection
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
     * @param array $products
     */
    public function updateArticleLinks(array $products)
    {
        if (count($products) === 0) {
            return;
        }

        $repository = $this->modelManager->getRepository(Product::class);

        $productsWithRelatedNewArticles = $repository->getProductsHavingRelatedArticles($products);
        foreach ($productsWithRelatedNewArticles as $product) {
            $this->setRelatedArticles($product);
        }

        $productsWithSimilarNewArticles = $repository->getProductsHavingSimilarArticles($products);
        foreach ($productsWithSimilarNewArticles as $product) {
            $this->setSimilarArticles($product);
        }
    }
}