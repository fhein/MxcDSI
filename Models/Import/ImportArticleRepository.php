<?php

namespace MxcDropshipInnocigs\Models\Import;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

class ImportArticleRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('a')
            -> select('a')
            -> indexBy('a', 'a.number')
            -> getQuery()
            ->getResult();
    }
}