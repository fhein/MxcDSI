<?php

namespace MxcDropshipInnocigs\Models;

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
