<?php

namespace MxcDropshipInnocigs\Models;


class GroupRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'  => 'SELECT g FROM MxcDropshipInnocigs\Models\Group g INDEX BY g.name',
        'removeOrphaned' => 'DELETE MxcDropshipInnocigs\Models\Group g WHERE g.options is empty',
    ];

    public function removeOrphaned()
    {
        return $this->getQuery(__FUNCTION__)->execute();
    }
}
