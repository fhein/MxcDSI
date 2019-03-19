<?php

namespace MxcDropshipInnocigs\Models;

class GroupRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'  => 'SELECT g FROM MxcDropshipInnocigs\Models\Group g INDEX BY g.name',
        'removeOrphaned' => 'SELECT g FROM MxcDropshipInnocigs\Models\Group g WHERE g.options is empty',
    ];

    public function getAllIndexed()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function removeOrphaned()
    {
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Group $orphan */
        $em = $this->getEntityManager();
        foreach ($orphans as $orphan) {
            $this->log->debug('Removing orphaned group \'' . $orphan->getName() . '\'');
            $em->remove($orphan);
        }
    }
}
