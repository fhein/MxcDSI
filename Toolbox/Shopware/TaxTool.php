<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Shopware\Models\Tax\Tax;

class TaxTool
{
    /**
     * Create a Tax object for the given tax value.
     *
     * @param float $taxValue
     * @return Tax
     */
    public static function createTax(float $taxValue)
    {
        $name = sprintf('Tax (%.2f)', $taxValue);
        $tax = new Tax();
        Shopware()->Models()->persist($tax);
        $tax->setName($name);
        $tax->setTax($taxValue);
        return $tax;
    }

    /**
     * Returns a Tax object for the given tax value. If the requested Tax object does not exist
     * and $create is true the Tax object will be created.
     *
     * @param float $taxValue
     * @param bool $create
     * @return Tax
     */
    public static function getTax(float $taxValue = 19.0, bool $create = true)
    {
        $modelManager = Shopware()->Models();
        $tax = $modelManager->getRepository(Tax::class)->findOneBy(['tax' => $taxValue]);
        if (! $tax && $create) {
            $tax = self::createTax($taxValue);
        }
        return $tax;
    }
}