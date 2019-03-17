<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Query;
use MxcDropshipInnocigs\Mapping\EntitiyValidator;

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
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function getAllWithRelated(array $relatedIds)
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
            ->setParameter('relatedIds', $relatedIds)->getResult();
    }

    public function getAllWithSimilar(array $relatedIds)
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
            ->setParameter('similarIds', $relatedIds)->getResult();
    }

    public function getLinked()
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function getAllIndexedByName()
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function getFlavoredIndexed()
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function getValidVariants(Article $article) : array
    {
        $validator = new EntitiyValidator();
        $validVariants = [];
        $variants = $article->getVariants();
        foreach ($variants as $variant) {
            if ($validator->validateVariant($variant)) {
                $validVariants[] = $variant;
            }
        }
        return $validVariants;
    }


    public function getShopwareArticle(Article $article)
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
            ->setParameter('number', $article->getNumber())->getResult()[0];

//        $orderNumbers = [];
//        $variants = $article->getVariants();
//        foreach ($variants as $variant) {
//            $orderNumbers[] = $variant->getNumber();
//        }
//        $details = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
//            ->setParameter('ordernumbers', $orderNumbers)
//            ->getResult();
//
//        if ($details[0] === null) {
//            return null;
//        }
//        /** @noinspection PhpUndefinedMethodInspection */
//        return $details[0]->getArticle();

    }

    public function getAllHavingShopwareArticleIndexed()
    {
        $icArticles = $this->getAllIndexed();
        $result = [];
        foreach ($icArticles as $icArticle) {
            if ($this->getShopwareArticle($icArticle) !== null) {
                $result[] = $icArticle;
            }
        }
        return $result;
    }


    public function removeOrphaned()
    {
        $orphans = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
        /** @var Article $orphan */
        foreach ($orphans as $orphan) {
            $this->log->debug('Removing orphaned article \'' . $orphan->getName() . '\'');
            $this->getEntityManager()->remove($orphan);
        }
    }

    public function getDist()
    {
        $result = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
            ->setParameter('manufacturers', ['SC', 'InnoCigs', 'Steamax'])
            ->getResult(Query::HYDRATE_ARRAY);
        return array_merge($result, $this->getSuppliersAndBrands('Akkus'));
    }

    public function getSuppliersAndBrands($manufacturers = null)
    {
        if (null === $manufacturers) {
            return $this->getAllSuppliersAndBrands();
        }
        return $this->getEntityManager()
            ->createQuery($this->dql[__FUNCTION__])
            ->setParameter('manufacturers', is_string($manufacturers) ? [$manufacturers] : $manufacturers)
            ->getResult(Query::HYDRATE_ARRAY);
    }

    public function getAllSuppliersAndBrands()
    {
        return $this->getEntityManager()
            ->createQuery($this->dql[__FUNCTION__])
            ->getResult(Query::HYDRATE_ARRAY);
    }
}
