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
}
