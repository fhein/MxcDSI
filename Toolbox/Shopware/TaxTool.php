<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Shopware\Models\Tax\Tax;

class TaxTool
{
    /**
     * Returns a Tax object for the given tax value. If the requested Tax object does not exist
     * it will be created.
     *
     * @param float $taxValue
     * @return Tax
     */
    public static function getTax(float $taxValue = 19.0)
    {
        $modelManager = Shopware()->Models();
        $tax = $modelManager->getRepository(Tax::class)->findOneBy(['tax' => $taxValue]);
        if (!$tax instanceof Tax) {
            $name = sprintf('Tax (%.2f)', $taxValue);
            $tax = new Tax();
            $modelManager->persist($tax);
            $tax->setName($name);
            $tax->setTax($taxValue);
        }
        return $tax;
    }
}