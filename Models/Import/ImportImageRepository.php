<?php

namespace MxcDropshipInnocigs\Models\Import;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

class ImportImageRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('i')
            -> select('i')
            -> indexBy('i', 'i.url')
            -> getQuery()
            ->getResult();
    }
}