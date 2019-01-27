<?php

namespace MxcDropshipInnocigs\Models\Current;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

class VariantRepository extends BaseEntityRepository
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
