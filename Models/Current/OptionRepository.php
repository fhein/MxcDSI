<?php

namespace MxcDropshipInnocigs\Models\Current;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

class OptionRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('o')
            -> select('o')
            -> indexBy('o', 'o.name')
            -> getQuery()
            ->getResult();
    }
}
