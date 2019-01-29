<?php

namespace MxcDropshipInnocigs\Models;

class VariantRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('v')
            -> select('v')
            -> indexBy('v', 'v.number')
            -> getQuery()
            ->getResult();
    }

    public function removeOrphaned() {
        $orphans = $this->createQueryBuilder('v')
            ->select('v')
            ->where('v.article = null')
            ->getQuery()
            ->getResult();

        foreach($orphans as $orphan) {
            $this->getEntityManager()->remove($orphan);
        }
    }
}
