<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Query;

class ArticleRepository extends BaseEntityRepository
{
    /** @var Query $supplierBrandByManufacturerQuery */
    protected $supplierBrandByManufacturerQuery;

    protected $getAllIndexedDql = 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.number';

    public function getAllIndexed()
    {
        return $this->getEntityManager()->createQuery($this->getAllIndexedDql)->getResult();
    }

    public function removeOrphaned() {
        $query = $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.variants is empty')
            ->getQuery();
        $this->log->debug('Article#removeOrphans: ' . $query->getDQL());
        $orphans = $query->getResult();
        /** @var Article $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned article \'' . $orphan->getName() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }

    /** @var Query $supplierBrandQuery */
    protected $supplierBrandQuery;

    protected $innocigsBrands = [
        'SC',
        'Steamax',
        'InnoCigs',
    ];

    protected function getSupplierBrandBuilder() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('a')
            ->select('a.icNumber, a.name, a.brand, a.supplier, a.category')
            ->indexBy('a', 'a.icNumber');
    }

    protected function getSupplierAndBrandAllQuery() {
        if ($this->supplierBrandQuery === null) {
            $this->supplierBrandQuery = $this->getSupplierBrandBuilder()->getQuery();
        }
        return $this->supplierBrandQuery;
    }

    protected function getSupplierBrandByManufacturerQuery() {
        if ($this->supplierBrandByManufacturerQuery === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->supplierBrandByManufacturerQuery = $this->getSupplierBrandBuilder()
                ->where('a.manufacturer IN (:manufacturers)')
                ->getQuery();
        }
        return $this->supplierBrandByManufacturerQuery;
    }

    public function getSupplierAndBrandByManufacturer($manufacturers) {
        if (is_string($manufacturers)) {
            $manufacturers = [$manufacturers];
        }
        return $this->getSupplierBrandByManufacturerQuery()
            ->setParameter('manufacturers', $manufacturers)
            ->getResult(Query::HYDRATE_ARRAY);
    }

    public function getDist() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->createQueryBuilder('a')
            ->select('a.icNumber, a.name, a.supplier, a.category')
            ->indexBy('a', 'a.icNumber')
            ->where('a.manufacturer IN (:manufacturers)')
            ->setParameter('manufacturers', $this->innocigsBrands)
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);
        return array_merge($result, $this->getSupplierAndBrandByManufacturer('Akkus'));
    }

    public function getAllSuppliersAndBrands() {
        return $this->getSupplierAndBrandAllQuery()->getResult(Query::HYDRATE_ARRAY);
    }
}
