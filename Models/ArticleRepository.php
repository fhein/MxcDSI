<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Query;

class ArticleRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'                      => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',

        'getArticlesByIds'                   => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a WHERE a.id in (:ids)',

                                                // get all articles which have an associated Shopware Article
        'getLinkedIndexed'                   => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number',
        'getLinkedArticleIds'                => 'SELECT a.icNumber FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number',
        'getLinkedArticlesHavingOptions'    => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN v.options o WHERE o.id IN (:optionIds)',
                                                // get all articles which have an associated Shopware Article
                                                // that have related articles with :relatedIds
        'getAllHavingRelatedArticles'        => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN a.relatedArticles r  WHERE r.icNumber IN (:relatedIds)',
                                                // get all articles which have an associated Shopware Article
                                                // that have similar articles with :simularIds
        'getAllHavingSimilarArticles'        => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN a.similarArticles s  WHERE s.icNumber IN (:similarIds)',
        'getFlavoredArticles'                => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber WHERE a.flavor IS NOT NULL',
        'getShopwareArticle'                 => 'SELECT DISTINCT s FROM Shopware\Models\Article\Article s '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.article = s.id '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.number = d.number '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Article a WHERE v.article = a.id '
                                                    . 'WHERE a.number = :number',
        // 'getShopwareArticle'                 => 'SELECT d FROM Shopware\Models\Article\Detail d WHERE d.number IN (:ordernumbers)',
        'getDist'                            => 'SELECT a.icNumber, a.name, a.supplier, a.category FROM MxcDropshipInnocigs\Models\Article a '
                                                    . 'INDEX BY a.icNumber WHERE a.manufacturer IN (:manufacturers)',
        'getAllSuppliersAndBrands'           => 'SELECT a.icNumber, a.name, a.brand, a.supplier, a.category FROM MxcDropshipInnocigs\Models\Article a '
                                                    . 'INDEX BY a.icNumber',
        'getSuppliersAndBrands'              => 'SELECT a.icNumber, a.name, a.brand, a.supplier, a.category FROM MxcDropshipInnocigs\Models\Article a '
                                                    . 'INDEX BY a.icNumber WHERE a.manufacturer IN (:manufacturers)',
        'removeOrphaned'                     => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a WHERE a.variants is empty',

        'getProperties'                      => 'SELECT :properties FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',
    ];

    public function getAllIndexed()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getArticlesByIds(array $ids) {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('ids', $ids)
            ->getResult();
    }

    public function getAllHavingRelatedArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('relatedIds', $relatedIds)
            ->getResult();
    }

    public function getAllHavingSimilarArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('similarIds', $relatedIds)->getResult();
    }

    public function getLinkedIndexed()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getLinkedArticleIds()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getFlavoredArticles()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getLinkedArticlesHavingOptions($optionIds) {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('optionIds', $optionIds)->getResult();
    }

    public function getValidVariants(Article $article) : array
    {
        $validVariants = [];
        $variants = $article->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            if ($variant->isValid()) {
                $validVariants[] = $variant;
            }
        }
        return $validVariants;
    }


    public function getShopwareArticle(Article $article)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('number', $article->getNumber())->getResult()[0];
    }

    public function removeOrphaned()
    {
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Article $orphan */
        $em = $this->getEntityManager();
        foreach ($orphans as $orphan) {
            $this->log->debug('Removing orphaned article \'' . $orphan->getName() . '\'');
            $em->remove($orphan);
        }
    }

    public function getDist()
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('manufacturers', ['SC', 'InnoCigs', 'Steamax'])
            ->getResult(Query::HYDRATE_ARRAY);
        return array_merge($result, $this->getSuppliersAndBrands('Akkus'));
    }

    public function getSuppliersAndBrands($manufacturers = null)
    {
        if (null === $manufacturers) {
            return $this->getAllSuppliersAndBrands();
        }
        return $this->getQuery(__FUNCTION__)
            ->setParameter('manufacturers', is_string($manufacturers) ? [$manufacturers] : $manufacturers)
            ->getResult(Query::HYDRATE_ARRAY);
    }

    public function getAllSuppliersAndBrands()
    {
        return $this->getQuery(__FUNCTION__)->getResult(Query::HYDRATE_ARRAY);
    }

    public function getProperties(array $properties)
    {
        $parameters = [];
        foreach ($properties as $property) {
            $parameters[] = 'a.' . $property;
        }
        $parameters = implode(', ', $parameters);
        $dql = $this->dql[__FUNCTION__];
        $dql = str_replace(':properties', $parameters, $dql);
        return $this->getEntityManager()
            ->createQuery($dql)
            ->getResult();
    }

    /**
     * An article validates true if either it's $accepted member is true
     * and at least one of the article's variants validates true
     *
     * @param Article $article
     * @return bool
     */
    public function validateArticle(Article $article) : bool
    {
        if (! $article->isAccepted()) {
            return false;
        }
        $variants = $article->getVariants();
        foreach($variants as $variant) {
            if ($variant->isValid()) {
                return true;
            }
        }
        return false;
    }
}
