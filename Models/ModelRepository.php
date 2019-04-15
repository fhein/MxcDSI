<?php

namespace MxcDropshipInnocigs\Models;

class ModelRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed' => 'SELECT ir FROM MxcDropshipInnocigs\Models\Model ir INDEX BY ir.model WHERE ir.deleted = :deleted'
    ];

    public function getAllIndexed() {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('deleted', false)
            ->getResult();
    }

    public function getAllDeletetedIndexed() {
        return $this->getQuery('getAllIndexed')
            ->setParameter('deleted', true)
            ->getResult();
    }
}