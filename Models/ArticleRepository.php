<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Query;
use MxcDropshipInnocigs\Mapping\EntitiyValidator;

class ArticleRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'                      => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',
        'getAllIndexedByName'                => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.name',
        'getFlavoredIndexed'                 => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber WHERE a.flavor IS NOT NULL',
        'getShopwareArticle'                 => 'SELECT d FROM Shopware\Models\Article\Detail d WHERE d.number IN (:ordernumbers)',
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
        $orderNumbers = [];
        $variants = $article->getVariants();
        foreach ($variants as $variant) {
            $orderNumbers[] = $variant->getNumber();
        }
        $details = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
            ->setParameter('ordernumbers', $orderNumbers)
            ->getResult();

        if ($details[0] === null) {
            return null;
        }
        /** @noinspection PhpUndefinedMethodInspection */
        return $details[0]->getArticle();

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
