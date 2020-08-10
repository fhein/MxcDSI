<?php

namespace MxcDropshipIntegrator\Models;

class ModelRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'             => 'SELECT ir FROM MxcDropshipIntegrator\Models\Model ir INDEX BY ir.model WHERE ir.deleted = :deleted',
        'getModelsWithoutVariant'   => 'SELECT m FROM MxcDropshipIntegrator\Models\Model m '
                                        . 'LEFT JOIN MxcDropshipIntegrator\Models\Variant v WITH m.model = v.icNumber '
                                        . 'WHERE v.id IS NULL',
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