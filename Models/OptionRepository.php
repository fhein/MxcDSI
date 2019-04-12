<?php

namespace MxcDropshipInnocigs\Models;

class OptionRepository extends BaseEntityRepository
{
    protected $dql = [
        'removeOrphaned'    => 'DELETE MxcDropshipInnocigs\Models\Option o WHERE o.variants is empty',
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
        $this->getQuery(__FUNCTION__)->execute();
    }
}
