<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\UnitTool;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price;
use Shopware\Models\Customer\Group;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class PriceMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var array */
    protected $customerGroups;

    public function __construct()
    {
        $this->modelManager = Shopware()->Models();
        $customerGroups = $this->modelManager->getRepository(Group::class)->findAll();
        /** @var Group $customerGroup */
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroups[$customerGroup->getKey()] = $customerGroup;
        }
    }
    /**
     * Returns the price object for the customer group with the given key of the given
     * Shopware detail object. If the price object is not found it will be created and
     * added to the Shopware detail object.
     *
     * @param Detail $detail
     * @param string $customerGroupKey
     * @return Price|null
     */
    public function getPrice(Detail $detail, string $customerGroupKey): ?Price
    {
        $customerGroup = $this->customerGroups[$customerGroupKey];
        if ($customerGroup === null) {
            return null;
        }

        $prices = $detail->getPrices();
        /** @var Price $price */
        foreach ($prices as $price) {
            if ($price->getCustomerGroup()->getKey() === $customerGroupKey) {
                return $price;
            }
        }
        $price = $this->createPrice($detail, $customerGroup);
        $detail->getPrices()->add($price);
        return $price;
    }

    /**
     * Creates and returns a price object for the customer group identified by $key
     * which is related to the given Shopware detail and the assiciated Shopware article.
     *
     * @param Detail $detail
     * @param Group $customerGroup
     * @return Price
     */
    public function createPrice(Detail $detail, Group $customerGroup)
    {
        $price = new Price();
        $this->modelManager->persist($price);
        $price->setCustomerGroup($customerGroup);
        // important to avoid 'not configured for cascade persist
        $this->modelManager->persist($customerGroup);
        $price->setArticle($detail->getArticle());
        $price->setDetail($detail);
        return $price;
    }

    public function getCustomerGroups()
    {
        return $this->customerGroups;
    }

    /**
     * Set the retail price of Shopware detail associated to the given InnoCigs variant
     *
     * @param Variant $variant
     */
    public function setRetailPrices(Variant $variant)
    {
        $detail = $variant->getDetail();
        if (!$detail) return;
        if (!$variant->getRetailPrices()) return;

        $tax = $detail->getArticle()->getTax()->getTax();

        $retailPrices = explode(MXC_DELIMITER_L2, $variant->getRetailPrices());
        foreach ($retailPrices as $retailPrice) {
            list($customerGroupKey, $retailPrice) = explode(MXC_DELIMITER_L1, $retailPrice);
            $price = $this->getPrice($detail, $customerGroupKey);

            if (!$price) {
                continue;
            }
            $retailPrice = floatval(str_replace(',', '.', $retailPrice));
            $netPrice = $retailPrice / (1 + ($tax / 100));
            $price->setPrice($netPrice);
            $price->setFrom(1);
            $price->setTo(null);
        }
    }

    /**
     * Set the reference price for liquid articles. The article name must
     * include the content in ml and the category name must include 'Liquid',
     * 'Aromen', 'Basen' or 'Shake & Vape'.
     *
     * @param Product $product
     */
    public static function setReferencePrice(Product $product)
    {
        // These products may need a reference price, unit is ml
        if (preg_match('~(Liquid)|(Aromen)|(Basen)|(Shake \& Vape)~', $product->getCategory()) !== 1) {
            return;
        }
        // Do not add reference price on multi item packs
        $name = $product->getName();
        if (preg_match('~\(\d+ Stück pro Packung\)~', $name) === 1) {
            return;
        }

        $matches = [];
        preg_match('~(\d+(\.\d+)?) ml~', $name, $matches);
        // If there's there are no ml in the product name we exit
        if (empty($matches)) {
            return;
        }

        // remove thousands punctuation
        $baseVolume = $matches[1];
        $baseVolume = str_replace('.', '', $baseVolume);

        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $detail = $variant->getDetail();
            if (!$detail) {
                continue;
            }
            $pieces = $variant->getPiecesPerOrder();
            // calculate the reference volume
            $volume = $baseVolume * $pieces;
            $reference = $volume < 100 ? 100 : ($volume < 1000 ? 1000 : 0);
            // Exit if we have no reference volume
            if ($reference === 0) {
                continue;
            }

            // set reference volume and unit
            $detail->setPurchaseUnit($volume);
            $detail->setReferenceUnit($reference);
            $detail->setUnit(UnitTool::getUnit('ml'));
        }
    }
}