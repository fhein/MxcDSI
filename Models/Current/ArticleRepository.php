<?php

namespace MxcDropshipInnocigs\Models\Current;

use Doctrine\ORM\Query;
use MxcDropshipInnocigs\Models\BaseEntityRepository;

class ArticleRepository extends BaseEntityRepository
{
    /** @var Query $supplierBrandQuery */
    protected $supplierBrandQuery;

    protected function getSupplierBrandQuery() {
        if ($this->supplierBrandQuery === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->supplierBrandQuery = $this->getEntityManager()->createQueryBuilder()
                ->select('a.code, a.name, a.brand, a.supplier')
                ->from('MxcDropshipInnocigs\Models\Current\Article', 'a', 'a.code')
                ->where('a.manufacturer IN (:manufacturers)')
                ->getQuery();
        }
        return $this->supplierBrandQuery;
    }

    public function getSupplierBrand($manufacturers) {
        if (is_string($manufacturers)) {
            $manufacturers = [$manufacturers];
        }
        $query = $this->getSupplierBrandQuery();
        return $query->setParameter('manufacturers', $manufacturers)->getResult(Query::HYDRATE_ARRAY);
    }
}
