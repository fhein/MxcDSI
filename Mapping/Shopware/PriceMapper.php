<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping\Shopware;

use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\TaxTool;
use MxcDropshipInnocigs\Toolbox\Shopware\UnitTool;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price;
use Shopware\Models\Customer\Group;

class PriceMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var array */
    protected $customerGroups;

    protected $customerGroupRepository;

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
        /** @var Group $customerGroup */
        $customerGroup = $this->getCustomerGroupRepository()->findOneBy(['key' => $customerGroupKey]);
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

        $retailPrices = explode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $variant->getRetailPrices());
        foreach ($retailPrices as $retailPrice) {
            [$customerGroupKey, $retailPrice] = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $retailPrice);
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

        $type = $product->getType();
        if (! in_array($type, ['LIQUID', 'LIQUID_BOX', 'AROMA', 'SHAKE_VAPE', 'BASE', 'EASY3_CAP'])) return;

        $reference = $type === 'BASE' ? 1000 : 100;

        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $detail = $variant->getDetail();
            if ($detail === null) continue;

            $content = $variant->getContent() * $variant->getPiecesPerOrder();

            // set reference volume and unit
            $detail->setPurchaseUnit($content);
            $detail->setReferenceUnit($reference);
            $detail->setUnit(UnitTool::getUnit('ml'));

        }
    }

    /**
     * Passt bei allen Produkten den den Mehrwertsteuersatz an den aktuell geltenden Wert an.
     * Die Gültigkeitsperioden der Mehrwertsteuersätze sind im TaxTool konfiguriert.
     *
     * Wir arbeiten bei vapee.de ausschließlich mit dem Standard-Mehrwertsteuersatz
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateVat()
    {
        $articles = $this->modelManager->getRepository(Article::class)->findAll();
        $tax = TaxTool::getTax();
        /** @var Article $article */
        foreach ($articles as $article) {
            $article->setTax($tax);
        }
        $this->modelManager->flush();

        $currentVatPercentage = TaxTool::getCurrentVatPercentage();
        $products = $this->modelManager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $product->setTax($currentVatPercentage);
        }
        $this->modelManager->flush();
    }

    protected function getCustomerGroupRepository()
    {
        return $this->customerGroupRepository ?? $this->customerGroupRepository = $this->modelManager->getRepository(Group::class);
    }
}