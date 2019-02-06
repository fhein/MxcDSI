<?php

namespace MxcDropshipInnocigs\Models;

class ImageRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'     => 'SELECT i FROM MxcDropshipInnocigs\Models\Image i INDEX BY i.url',
        'removeOrphaned'    => 'SELECT i FROM MxcDropshipInnocigs\Models\Image i WHERE i.variants is empty',
    ];

    public function getAllIndexed() {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function removeOrphaned() {
        $orphans = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
        /** @var Image $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned image \'' . $orphan->getUrl() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }

}
