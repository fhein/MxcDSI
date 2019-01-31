<?php

namespace MxcDropshipInnocigs\Models;

class ModelRepository extends BaseEntityRepository
{
    protected $getAllIndexed = 'SELECT m FROM MxcDropshipInnocigs\Models\Model m INDEX BY m.model WHERE m.deleted = :deleted';

    public function getAllIndexed() {
        return $this->getEntityManager()->createQuery($this->getAllIndexed)
            ->setParameter('deleted', 'false')
            ->getResult();
    }

    public function getAllDeletetedIndexed() {
        return $this->getEntityManager()->createQuery($this->getAllIndexed)
            ->setParameter('deleted', 'false')
            ->getResult();
    }
}