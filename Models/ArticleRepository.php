<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Query;

class ArticleRepository extends BaseEntityRepository
{
    /** @var Query $supplierBrandByManufacturerQuery */
    protected $supplierBrandByManufacturerQuery;

    /** @var Query $supplierBrandQuery */
    protected $supplierBrandQuery;

    protected $dql = [
        'getAllIndexed'             => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',

        'getDist'                   => 'SELECT a.icNumber, a.name, a.supplier, a.category FROM MxcDropshipInnocigs\Models\Article a '
                                            . 'INDEX BY a.icNumber WHERE a.manufacturer IN (:manufacturers)',

        'getAllSuppliersAndBrands'  => 'SELECT a.icNumber, a.name, a.brand, a.supplier, a.category FROM MxcDropshipInnocigs\Models\Article a '
                                            . 'INDEX BY a.icNumber',

        'getSuppliersAndBrands'     => 'SELECT a.icNumber, a.name, a.brand, a.supplier, a.category FROM MxcDropshipInnocigs\Models\Article a '
                                            . 'INDEX BY a.icNumber WHERE a.manufacturer IN (:manufacturers)',

        'removeOrphaned'            => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a WHERE a.variants is empty',
    ];

    public function getAllIndexed()
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function removeOrphaned() {
        $orphans = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
        /** @var Article $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned article \'' . $orphan->getName() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }

    public function getAllSuppliersAndBrands()
    {
        return $this->getEntityManager()
            ->createQuery($this->dql[__FUNCTION__])
            ->getResult(Query::HYDRATE_ARRAY);
    }

    public function getSuppliersAndBrands($manufacturers = null) {
        if (null === $manufacturers) return $this->getAllSuppliersAndBrands();
        return $this->getEntityManager()
            ->createQuery($this->dql[__FUNCTION__])
            ->setParameter('manufacturers', is_string($manufacturers) ? [ $manufacturers] : $manufacturers)
            ->getResult(Query::HYDRATE_ARRAY);
    }

    public function getDist() {
        $result = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
            ->setParameter('manufacturers', [ 'SC', 'InnoCigs', 'Steamax'])
            ->getResult(Query::HYDRATE_ARRAY);
        return array_merge($result, $this->getSuppliersAndBrands('Akkus'));
    }
}
