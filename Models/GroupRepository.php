<?php

namespace MxcDropshipIntegrator\Models;


class GroupRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'  => 'SELECT g FROM MxcDropshipIntegrator\Models\Group g INDEX BY g.name',
        'removeOrphaned' => 'DELETE MxcDropshipIntegrator\Models\Group g WHERE g.options is empty',
    ];

    public function removeOrphaned()
    {
        return $this->getQuery(__FUNCTION__)->execute();
    }
}
