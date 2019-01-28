<?php

namespace MxcDropshipInnocigs\Models;

class ImageRepository extends BaseEntityRepository
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
