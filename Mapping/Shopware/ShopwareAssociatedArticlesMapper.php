<?php


namespace MxcDropshipInnocigs\Mapping\Shopware;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MxcDropshipInnocigs\Models\Article;
use Shopware\Models\Article\Article as ShopwareArticle;

class ShopwareAssociatedArticlesMapper
{
    /** @var array */
    protected $associatedArticles;

    /**
     * @param array $activeArticles
     * @return array
     */
    public function getAssociatedArticles(array $activeArticles): array
    {
        $this->associatedArticles = [];
        foreach ($activeArticles as $icArticle) {
            $this->prepareAssociatedArticles($icArticle);
        }
        return $this->associatedArticles;
    }

    /**
     * Fill the $this->associatedArticles recursively
     *
     * @param Article $icArticle
     */
    public function prepareAssociatedArticles(Article $icArticle)
    {
        // exit recursion if $icArticle is registered already
        if ($this->associatedArticles[$icArticle->getIcNumber()]) {
            return;
        }

        $this->prepareAssociatedArticlesCollection(
            $icArticle->getRelatedArticles(),
            $icArticle->getCreateRelatedArticles(),
            $icArticle->getActivateCreatedRelatedArticles()
        );

        $this->prepareAssociatedArticlesCollection(
            $icArticle->getSimilarArticles(),
            $icArticle->getCreateSimilarArticles(),
            $icArticle->getActivateCreatedSimilarArticles()
        );
    }

    /**
     * @param Collection $icArticles
     * @param bool $createAssociated
     * @param bool $activateAssociated
     */
    public function prepareAssociatedArticlesCollection(
        Collection $icArticles,
        bool $createAssociated,
        bool $activateAssociated
    ) {
        /** @var Article $article */
        foreach ($icArticles as $article) {
            $isNew = $article->getArticle() === null;
            if (!$createAssociated && $isNew) {
                continue;
            }
            if ($isNew) {
                $article->setActive($activateAssociated);
            }
            $this->associatedArticles[$article->getIcNumber()] = $article;

            // Recursion
            $this->prepareAssociatedArticles($article);
        }
    }

    /**
     * Set the related articles of a Shopware article according to the settings of the InnoCigs article.
     * If the $replace flag is true, the related articles of the Shopware article will be replaced. If the
     * $replace flag is false, new related articles will be added, if any.
     *
     * @param Article $icArticle
     * @param bool $replace true: replace related articles, false: add related articles
     */
    public function setRelatedArticles(Article $icArticle, bool $replace = false)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (!$swArticle) {
            return;
        }

        $related = $swArticle->getRelated();
        if ($replace) {
            $related->clear();
        }
        $relatedArticles = $this->getShopwareArticles($icArticle->getRelatedArticles());
        $this->addArticlesToCollection($relatedArticles, $related);
    }

    /**
     * Set the similar articles of a Shopware article according to the settings of the InnoCigs article.
     * If the $replace flag is true, the similar articles of the Shopware article will be replaced. If the
     * $replace flag is false, new similar articles will be added, if any.
     *
     * @param Article $icArticle
     * @param bool $replace true: replace related articles, false: add related articles
     */
    public function setSimilarArticles(Article $icArticle, bool $replace = false)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (!$swArticle) {
            return;
        }

        $similar = $swArticle->getSimilar();
        if ($replace) {
            $similar->clear();
        }
        $similarArticles = $this->getShopwareArticles($icArticle->getSimilarArticles());
        $this->addArticlesToCollection($similarArticles, $similar);
    }

    /**
     * Add all articles of the given Shopware article collection to the target collection.
     * No duplicates.
     *
     * @param Collection $swArticles
     * @param Collection $collection
     */
    public function addArticlesToCollection(Collection $swArticles, Collection $collection)
    {
        foreach ($swArticles as $article) {
            if (!$collection->contains($article)) {
                $collection->add($article);
            }
        }
    }

    /**
     * For a given collection of InnoCigs articles return a collection of all associated Shopware articles.
     *
     * @param Collection $icArticles
     * @return ArrayCollection
     */
    public function getShopwareArticles(Collection $icArticles): ArrayCollection
    {
        $swArticles = [];
        foreach ($icArticles as $icArticle) {
            $swArticle = $icArticle->getArticle();
            if ($swArticle !== null) {
                $swArticles[] = $swArticle;
            }
        }
        return new ArrayCollection($swArticles);
    }
}