<?php

namespace MxcDropshipInnocigs\Models;

class OptionRepository extends BaseEntityRepository
{
    public function getAllIndexed() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $options = $this->createQueryBuilder('o')
            ->select('o')
            ->getQuery()
            ->getResult();
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
        $orphans = $this->createQueryBuilder('o')
            ->select('o')
            ->where('o.variants is empty')
            ->getQuery()
            ->getResult();
        /** @var Option $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned option \'' . $orphan->getName() .'\'');
            $orphan->getIcGroup()->removeOption($orphan);
            $this->getEntityManager()->remove($orphan);
        }
    }
}
