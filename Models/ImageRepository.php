<?php

namespace MxcDropshipInnocigs\Models;

class ImageRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('i')
            -> select('i')
            -> indexBy('i', 'i.url')
            -> getQuery()
            ->getResult();
    }

    public function removeOrphaned() {
        $orphans = $this->createQueryBuilder('i')
            ->select('i')
            ->where('i.variants is empty')
            ->getQuery()
            ->getResult();
        foreach($orphans as $orphan) {
            $this->getEntityManager()->remove($orphan);
        }
    }

}
