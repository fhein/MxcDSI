<?php

namespace MxcDropshipInnocigs\Models\Import;

use MxcDropshipInnocigs\Models\BaseEntityRepository;

class ImportOptionRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('o')
            ->select('o')
            -> leftJoin('o.icGroup', 'g')
            ->indexBy('o', "CONCAT(g.name, '#', o.name)")
            ->getQuery()
            ->getResult();
    }

    public function findOption(string $group, string $option) {
        return $this->createQueryBuilder('o')
            -> select('o')
            -> leftJoin('o.icGroup', 'g', 'WITH', 'g.name = :group')
            -> where('o.name = :option')
            ->setParameter('group', $group)
            ->setParameter('option', $option)
            ->getQuery()
            ->getResult()[0];
    }
}