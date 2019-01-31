<?php

namespace MxcDropshipInnocigs\Models;

class ImageRepository extends BaseEntityRepository
{
    protected $getAllIndexedDql = 'SELECT i FROM MxcDropshipInnocigs\Models\Image i INDEX BY i.url';

    public function getAllIndexed() {
        return $this->getEntityManager()->createQuery($this->getAllIndexedDql)->getResult();
    }

    public function removeOrphaned() {
        $query = $this->createQueryBuilder('i')
            ->select('i')
            ->where('i.variants is empty')
            ->getQuery();
        $this->log->debug('Image#removeOrphans: ' . $query->getDQL());
        $orphans = $query->getResult();
        /** @var Image $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned image \'' . $orphan->getUrl() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }

}
