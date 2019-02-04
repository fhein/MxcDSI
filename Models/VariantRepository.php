<?php

namespace MxcDropshipInnocigs\Models;

class VariantRepository extends BaseEntityRepository
{
    protected $getAllIndexedDql = 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v INDEX BY v.icNumber';

    public function getAllIndexed() {
        return $this->getEntityManager()->createQuery($this->getAllIndexedDql)->getResult();
    }

    public function removeOrphaned() {
        $query = $this->createQueryBuilder('v')
            ->select('v')
            ->where('v.article = null')
            ->getQuery();
        $this->log->debug('Variant#removeOrphans: ' . $query->getDQL());
        $orphans = $query->getResult();

        /** @var Variant $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned variant \'' . $orphan->getNumber() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }
}
