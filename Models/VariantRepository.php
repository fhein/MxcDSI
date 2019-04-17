<?php

namespace MxcDropshipInnocigs\Models;

use Shopware\Models\Article\Detail;

class VariantRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'      => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v INDEX BY v.icNumber',
        'getDetail'          => 'SELECT d FROM Shopware\Models\Article\Detail d WHERE d.number = :ordernumber',
        'getVariantByDetail' => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v WHERE v.number = (:number)',
        'removeOrphaned'     => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v WHERE v.product IS NULL',
    ];

    protected $sql = [
        'removeImages' => 'DELETE FROM s_plugin_mxc_dsi_x_variants_images WHERE variant_id = ?',
        'removeOptions' => 'DELETE FROM s_plugin_mxc_dsi_x_variants_options WHERE variant_id = ?',
    ];


    public function getDetail(Variant $variant)
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('ordernumber', $variant->getNumber())
            ->getResult();
        return $result[0] ?? null;
    }

    public function removeImages(Variant $variant)
    {
        $stmnt = $this->getStatement(__FUNCTION__);
        $stmnt->bindValue(1, $variant->getId());
        $stmnt->execute();
    }

    public function removeOptions(Variant $variant)
    {
        $stmnt = $this->getStatement(__FUNCTION__);
        $stmnt->bindValue(1, $variant->getId());
        $stmnt->execute();
    }

    public function getVariantByDetail(Detail $detail)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getQuery(__FUNCTION__)
            ->setParameter('number', $detail->getNumber())
            ->getSingleResult();
    }

    public function removeOrphaned()
    {
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Variant $orphan */
        $em = $this->getEntityManager();
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned variant \'' . $orphan->getNumber() .'\'');
            $em->remove($orphan);
        }
    }

    /**
     * A variant validates true if the $accepted member of the variant is true and
     * the $accepted member of the associated Product is true and all of the variant's
     * options validate true
     *
     * @param Variant $variant
     * @return bool
     */
    public function validateVariant(Variant $variant) : bool
    {
        if (! ($variant->isAccepted() && $variant->getProduct()->isAccepted())) {
            return false;
        }
        $options = $variant->getOptions();
        /** @var Option $option */
        foreach ($options as $option) {
            if (! $option->isValid()) {
                return false;
            }
        }
        return true;
    }

    public function getPiecesPerOrder(Variant $variant) {
        $options = $variant->getOptions();
        $matches = [];
        $pieces = 1;
        foreach ($options as $option) {
            preg_match('~(\d+)er Packung~', $option->getName(), $matches);
            if (empty($matches)) {
                continue;
            }
            $pieces =  $matches[1];
        }
        return $pieces;
    }
}
