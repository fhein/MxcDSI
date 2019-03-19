<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Query;

class ArticleRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'                      => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',
                                                // get all articles which have an associated Shopware Article
        'getLinked'                          => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number',
                                                // get all articles which have an associated Shopware Article
                                                // that have related articles with :relatedIds
        'getAllWithRelated'                  => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN a.relatedArticles r  WHERE r.icNumber IN (:relatedIds)',
                                                // get all articles which have an associated Shopware Article
                                                // that have similar articles with :simularIds
        'getAllWithSimilar'                  => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropShipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN a.similarArticles s  WHERE s.icNumber IN (:similarIds)',
        'getAllIndexedByName'                => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.name',
        'getFlavoredIndexed'                 => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber WHERE a.flavor IS NOT NULL',
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
    ];

    public function getAllIndexed()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getAllWithRelated(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('relatedIds', $relatedIds)
            ->getResult();
    }

    public function getAllWithSimilar(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('similarIds', $relatedIds)->getResult();
    }

    public function getLinked()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getAllIndexedByName()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getFlavoredIndexed()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
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
