<?php

namespace MxcDropshipInnocigs\Models;

class ModelRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('m')
            -> select('m')
            ->where('m.deleted = false')
            -> indexBy('m', 'm.model')
            -> getQuery()
            ->getResult();
    }
}