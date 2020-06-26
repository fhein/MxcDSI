<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Models\Customer\Group;

class PriceTool implements ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    private $customerGroups = null;

    /**
     * Liefert ein Array mit allen Schlüsseln der Shopware Kundengruppen (EK, H, usw.)
     *
     * @return array
     */
    public function getCustomerGroupKeys(): array
    {
        return array_keys($this->getCustomerGroups());
    }

    /**
     * Liefert ein Array der Shopware Kundengruppen indiziert nach den Schlüsseln (EK, H, usw.)
     *
     * @return array
     */
    public function getCustomerGroups(): array
    {
        if ($this->customerGroups !== null) return $this->customerGroups;
        $customerGroups = $this->modelManager->getRepository(Group::class)->findAll();
        /** @var Group $customerGroup */
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroups[$customerGroup->getKey()] = $customerGroup;
        }
        return $this->customerGroups;
    }

    public function getRetailPrices(Variant $variant)
    {
        $retailPrices = [];
        /** @var Variant $variant */
        $sPrices = explode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $variant->getRetailPrices());
        foreach ($sPrices as $sPrice) {
            [$key, $price] = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $sPrice);
            $retailPrices[$key] = floatVal(str_replace(',', '.', $price));
        }
        return $retailPrices;
    }
}