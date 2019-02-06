<?php

namespace MxcDropshipInnocigs\Models;

class VariantRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed' => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v INDEX BY v.icNumber',
        'removeOrphaned'    => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v WHERE v.article = null',
   ];

    public function getAllIndexed()
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function removeOrphaned()
    {
        $orphans = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
        /** @var Variant $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned variant \'' . $orphan->getNumber() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }
}
