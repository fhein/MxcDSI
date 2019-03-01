<?php

namespace MxcDropshipInnocigs\Models;

class VariantRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'     => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v INDEX BY v.icNumber',
        'getShopwareDetail' => 'SELECT d FROM Shopware\Models\Article\Detail d WHERE d.number = :ordernumber',
        'removeOrphaned'    => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v WHERE v.article = null',
   ];

    public function getAllIndexed()
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function getShopwareDetail(Variant $variant)
    {
        $result = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])
            ->setParameter('ordernumber', $variant->getNumber())
            ->getResult();
        return $result[0];
    }

    public function removeOrphaned()
    {
        $orphans = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
        /** @var Variant $orphan */
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned variant \'' . $orphan->getNumber() .'\'');
            $this->getEntityManager()->remove($orphan);
        }
    }
}
