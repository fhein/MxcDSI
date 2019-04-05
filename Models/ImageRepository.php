<?php

namespace MxcDropshipInnocigs\Models;

class ImageRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'     => 'SELECT i FROM MxcDropshipInnocigs\Models\Image i INDEX BY i.url',
        'removeOrphaned'    => 'SELECT i FROM MxcDropshipInnocigs\Models\Image i WHERE i.variants is empty',
    ];

    public function removeOrphaned() {
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Image $orphan */
        $em = $this->getEntityManager();
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned image \'' . $orphan->getUrl() .'\'');
            $em->remove($orphan);
        }
    }

}
