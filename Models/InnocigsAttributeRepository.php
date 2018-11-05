<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\EntityRepository;

class InnocigsAttributeRepository extends EntityRepository
{
    public function getActiveAttributes() {
        $em = $this->getEntityManager();
        $dql = 'SELECT a FROM MxcDropshipInnocigs\Models\InnocigsAttribute a JOIN a.variants v WHERE v.active = true';
        $query = $em->createQuery($dql);
        return $query->getResult();
    }
}