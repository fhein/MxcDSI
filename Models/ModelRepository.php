<?php

namespace MxcDropshipInnocigs\Models;

class ModelRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed' => 'SELECT m FROM MxcDropshipInnocigs\Models\Model m INDEX BY m.model WHERE m.deleted = :deleted'
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