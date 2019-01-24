<?php

namespace MxcDropshipInnocigs\Models\Import;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

class ImportGroupRepository extends BaseEntityRepository
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