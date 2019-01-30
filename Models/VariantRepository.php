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

        /** @var Variant $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned variant \'' . $orphan->getNumber() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }
}
