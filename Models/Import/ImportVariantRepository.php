<?php

namespace MxcDropshipInnocigs\Models\Import;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

class ImportVariantRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('v')
            -> select('v')
            -> indexBy('v', 'v.number')
            -> getQuery()
            ->getResult();
    }
}