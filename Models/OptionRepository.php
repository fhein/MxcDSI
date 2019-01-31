<?php

namespace MxcDropshipInnocigs\Models;

class OptionRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        $options = $this->findAll();
        $result = [];
        /** @var Option $option */
        foreach ($options as $option) {
            $gname = $option->getIcGroup()->getName();
            $oname = $option->getName();
            $result[$gname][$oname] = $option;
        }
        return $result;
    }

    public function removeOrphaned() {
        $query = $this->createQueryBuilder('o')
            ->select('o')
            ->where('o.variants is empty')
            ->getQuery();
        $this->log->debug('Option#removeOrphans: ' . $query->getDQL());
        $orphans = $query->getResult();

        /** @var Option $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned option \'' . $orphan->getName() .'\'');
            $orphan->getIcGroup()->removeOption($orphan);
            $this->getEntityManager()->remove($orphan);
        }
    }
}
