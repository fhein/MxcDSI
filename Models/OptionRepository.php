<?php

namespace MxcDropshipInnocigs\Models;

class OptionRepository extends BaseEntityRepository
{
    protected $dql = [
        'removeOrphaned'    => 'SELECT o FROM MxcDropshipInnocigs\Models\Option o WHERE o.variants is empty',
    ];

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
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Option $orphan */
        $em = $this->getEntityManager();
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned option \'' . $orphan->getName() .'\'');
            $orphan->getIcGroup()->removeOption($orphan);
            $em->remove($orphan);
        }
    }
}
