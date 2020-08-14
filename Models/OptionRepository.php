<?php

namespace MxcDropshipIntegrator\Models;

use MxcCommons\Toolbox\Models\BaseEntityRepository;

class OptionRepository extends BaseEntityRepository
{
    protected $dql = [
        'removeOrphaned'    =>  'DELETE MxcDropshipIntegrator\Models\Option o WHERE o.variants is empty',
        'getOption'         =>  'SELECT o FROM MxcDropshipIntegrator\Models\Option o '
                                . 'JOIN o.icGroup g WHERE o.name = :optionName AND g.name = :groupName',
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

    public function getOption($groupName, $optionName)
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('groupName', $groupName)
            ->setParameter('optionName', $optionName)
            ->getResult();
        return $result[0] ?? null;
    }

    public function removeOrphaned() {
        $this->getQuery(__FUNCTION__)->execute();
    }
}
