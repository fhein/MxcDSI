<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price;
use Shopware\Models\Customer\Group;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class PriceTool
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var array */
    protected $customerGroups;

    public function __construct()
    {
        $this->modelManager = Shopware()->Models();
        $customerGroups = $this->modelManager->getRepository(Group::class)->findAll();
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroups[$customerGroup->getKey()] = $customerGroup;
        }
    }
    /**
     * Returns the price object for the customer group with the given key of the given
     * Shopware detail object. If the price object is not found it will be created and
     * added to the Shopware detail object.
     *
     * @param Detail $swDetail
     * @param string $customerGroupKey
     * @return Price|null
     */
    public function getPrice(Detail $swDetail, string $customerGroupKey): ?Price
    {
        $customerGroup = $this->customerGroups[$customerGroupKey];
        if ($customerGroup === null) {
            return null;
        }

        $prices = $swDetail->getPrices();
        /** @var Price $price */
        foreach ($prices as $price) {
            if ($price->getCustomerGroup()->getKey() === $customerGroupKey) {
                return $price;
            }
        }
        $price = $this->createPrice($swDetail, $customerGroup);
        $swDetail->getPrices()->add($price);
        return $price;
    }

    /**
     * Creates and returns a price object for the customer group identified by $key
     * which is related to the given Shopware detail and the assiciated Shopware article.
     *
     * @param Detail $swDetail
     * @param Group $customerGroup
     * @return Price
     */
    public function createPrice(Detail $swDetail, Group $customerGroup)
    {
        $price = new Price();
        $this->modelManager->persist($price);
        $price->setCustomerGroup($customerGroup);
        $price->setArticle($swDetail->getArticle());
        $price->setDetail($swDetail);
        return $price;
    }

    public function getCustomerGroups()
    {
        return $this->customerGroups;
    }

    /**
     * Set the retail price of Shopware detail associated to the given InnoCigs variant
     *
     * @param Variant $icVariant
     */
    public function setRetailPrices(Variant $icVariant)
    {
        $swDetail = $icVariant->getDetail();
        if (!$swDetail) {
            return;
        }

        $tax = $swDetail->getArticle()->getTax()->getTax();

        $retailPrices = explode(MXC_DELIMITER_L2, $icVariant->getRetailPrices());
        foreach ($retailPrices as $retailPrice) {
            list($customerGroupKey, $retailPrice) = explode(MXC_DELIMITER_L1, $retailPrice);
            $price = $this->getPrice($swDetail, $customerGroupKey);

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
     * @param Article $icArticle
     */
    public static function setReferencePrice(Article $icArticle)
    {
        // These products may need a reference price, unit is ml
        if (preg_match('~(Liquid)|(Aromen)|(Basen)|(Shake \& Vape)~', $icArticle->getCategory()) !== 1) {
            return;
        }
        // Do not add reference price on multi item packs
        $name = $icArticle->getName();
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

        $icVariants = $icArticle->getVariants();
        /** @var Variant $icVariant */
        foreach ($icVariants as $icVariant) {
            $swDetail = $icVariant->getDetail();
            if (!$swDetail) {
                continue;
            }
            $pieces = $icVariant->getPiecesPerOrder();
            // calculate the reference volume
            $volume = $baseVolume * $pieces;
            $reference = $volume < 100 ? 100 : ($volume < 1000 ? 1000 : 0);
            // Exit if we have no reference volume
            if ($reference === 0) {
                continue;
            }

            // set reference volume and unit
            $swDetail->setPurchaseUnit($volume);
            $swDetail->setReferenceUnit($reference);
            $swDetail->setUnit(UnitTool::getUnit('ml'));
        }
    }
}