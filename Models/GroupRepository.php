<?php

namespace MxcDropshipInnocigs\Models;

class GroupRepository extends BaseEntityRepository
{
    protected $getAllIndexedDql = 'SELECT g FROM MxcDropshipInnocigs\Models\Group g INDEX BY g.name';

    public function getAllIndexed() {
        return $this->getEntityManager()->createQuery($this->getAllIndexedDql)->getResult();
    }

    public function removeOrphaned() {
        $query = $this->createQueryBuilder('g')
            ->select('g')
            ->where('g.options is empty')
            ->getQuery();
        $this->log->debug('Group#removeOrphans: ' . $query->getDQL());
        $orphans = $query->getResult();
        /** @var Group $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned group \'' . $orphan->getName() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }
}
