<?php


namespace MxcDropshipInnocigs\Mapping\Shopware;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Models\Product;
use Shopware\Models\Article\Article;

class AssociatedArticlesMapper implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /**
     * @param ProductMapper $productMapper
     * @param array $products
     * @param bool $create
     */
    public function processAssociatedProducts(ProductMapper $productMapper, array $products, bool $create)
    {
        $associatedProducts = $this->getAssociatedProducts($products);

        /** @var Product $product */
        foreach ($associatedProducts as $product) {
            if ($productMapper->updateArticle($product, $create)) {
                $products[$product->getIcNumber()] = $product;
            }
        }

        foreach ($products as $product) {
            $this->setRelatedArticles($product);
            $this->setSimilarArticles($product);
        }
    }

    /**
     * @param array $products
     * @return array
     */
    protected function getAssociatedProducts(array $products): array
    {
        $associatedProducts = [];
        foreach ($products as $product) {
            $this->prepareAssociatedProducts($product, $associatedProducts);
        }
        return $associatedProducts;
    }

    /**
     * Fill the $this->associatedArticles recursively
     *
     * @param Product $product
     * @param array $associatedProducts
     */
    protected function prepareAssociatedProducts(Product $product, array &$associatedProducts)
    {
        // exit recursion if $product is registered already
        if ($associatedProducts[$product->getIcNumber()]) {
            return;
        }

        $this->prepareAssociatedProductsCollection(
            $product->getRelatedProducts(),
            $associatedProducts,
            $product->getCreateRelatedProducts(),
            $product->getActivateCreatedRelatedProducts()
        );

        $this->prepareAssociatedProductsCollection(
            $product->getSimilarProducts(),
            $associatedProducts,
            $product->getCreateSimilarProducts(),
            $product->getActivateCreatedSimilarProducts()
        );
    }

    /**
     * @param Collection $products
     * @param array $associatedProducts
     * @param bool $createAssociated
     * @param bool $activateAssociated
     */
    protected function prepareAssociatedProductsCollection(
        Collection $products,
        array &$associatedProducts,
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
            $associatedProducts[$product->getIcNumber()] = $product;

            // Recursion
            $this->prepareAssociatedProducts($product, $associatedProducts);
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
    protected function setRelatedArticles(Product $product, bool $replace = false)
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
    protected function setSimilarArticles(Product $product, bool $replace = false)
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
    protected function addArticlesToCollection(Collection $articles, Collection $collection)
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