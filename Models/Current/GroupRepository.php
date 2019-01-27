<?php

namespace MxcDropshipInnocigs\Models\Current;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

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
}
