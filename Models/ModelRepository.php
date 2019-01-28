<?php

namespace MxcDropshipInnocigs\Models;

class ModelRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('m')
            -> select('m')
            -> indexBy('m', 'm.model')
            -> getQuery()
            ->getResult();
    }
}