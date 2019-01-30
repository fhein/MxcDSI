<?php

namespace MxcDropshipInnocigs\Models;

class GroupRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('g')
            -> select('g')
            -> indexBy('g', 'g.name')
            -> getQuery()
            ->getResult();
    }

    public function removeOrphaned() {
        $orphans = $this->createQueryBuilder('g')
            ->select('g')
            ->where('g.options is empty')
            ->getQuery()
            ->getResult();
        /** @var Group $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned group \'' . $orphan->getName() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }
}
