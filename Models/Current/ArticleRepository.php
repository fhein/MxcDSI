<?php

namespace MxcDropshipInnocigs\Models\Current;

use Doctrine\ORM\Query;
use MxcDropshipInnocigs\Models\BaseEntityRepository;

class ArticleRepository extends BaseEntityRepository
{
    /** @var Query $supplierBrandByManufacturerQuery */
    protected $supplierBrandByManufacturerQuery;

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
            ->select('a.icCode, a.name, a.brand, a.supplier, a.category')
            ->indexBy('a', 'a.icCode');
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
            ->select('a.icCode, a.name, a.supplier, a.category')
            ->indexBy('a', 'a.icCode')
            ->where('a.manufacturer IN (:manufacturers)')
            ->setParameter('manufacturers', $this->innocigsBrands)
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);
        return array_merge($result, $this->getSupplierAndBrandByManufacturer('Akkus'));
    }

    public function getAllSuppliersAndBrands() {
        return $this->getSupplierAndBrandAllQuery()->getResult(Query::HYDRATE_ARRAY);
    }
}
