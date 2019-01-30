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
        /** @var Image $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned image \'' . $orphan->getUrl() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }

}
