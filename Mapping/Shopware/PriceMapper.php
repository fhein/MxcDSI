<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping\Shopware;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\TaxTool;
use MxcCommons\Toolbox\Shopware\UnitTool;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price;
use Shopware\Models\Customer\Group;
use MxcCommons\Defines\Constants;

class PriceMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var array */
    protected $customerGroups;

    protected $priceEngine;

    protected $customerGroupRepository;

    public function __construct(PriceEngine $priceEngine)
    {
        $this->modelManager = Shopware()->Models();
        $customerGroups = $this->modelManager->getRepository(Group::class)->findAll();
        /** @var Group $customerGroup */
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroups[$customerGroup->getKey()] = $customerGroup;
        }
        $this->priceEngine = $priceEngine;
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

        $vatFactor = 1 + $detail->getArticle()->getTax()->getTax() / 100;

        $retailPrices = explode(Constants::DELIMITER_L2, $variant->getRetailPrices());
        foreach ($retailPrices as $retailPrice) {
            [$customerGroupKey, $retailPrice] = explode(Constants::DELIMITER_L1, $retailPrice);
            $price = $this->getPrice($detail, $customerGroupKey);
            if (!$price) continue;

            $netRetailPrice = StringTool::tofloat($retailPrice);
            $grossRetailPrice = $this->priceEngine->beautifyPrice($netRetailPrice * $vatFactor);
            $netRetailPrice = $grossRetailPrice / $vatFactor;

            $price->setPrice($netRetailPrice);
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
        if (in_array($type, ['NICSALT_LIQUID', 'LIQUID', 'LIQUID_BOX', 'AROMA', 'SHAKE_VAPE', 'BASE', 'EASY3_CAP'])) {
            $reference = $type === 'BASE' ? 1000 : 100;
        } elseif (in_array($type, ['EMPTY_BOTTLE', 'SQUONKER_BOTTLE'])) {
            $reference = null;
        } else return;

        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $detail = $variant->getDetail();
            if ($detail === null) continue;

            $content = $variant->getContent() * $variant->getPiecesPerOrder();
            if ($content === 0) continue;

            // set reference volume and unit
            $detail->setPurchaseUnit($content);
            $detail->setUnit(UnitTool::getUnit('ml'));
            if ($reference !== null) {
                $detail->setReferenceUnit($reference);
            }

        }
    }

    /**
     * Passt bei allen Produkten den den Mehrwertsteuersatz an den aktuell geltenden Wert an.
     * Die Gültigkeitsperioden der Mehrwertsteuersätze sind im TaxTool konfiguriert.
     *
     * Wir arbeiten bei vapee.de ausschließlich mit dem Standard-Mehrwertsteuersatz
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateVat()
    {
        // Set the tax member of all articles to the current vat percentage
        $articles = $this->modelManager->getRepository(Article::class)->findAll();
        $tax = TaxTool::getTax();
        /** @var Article $article */
        foreach ($articles as $article) {
            $article->setTax($tax);
        }
        $this->modelManager->flush();

        // Set the tax member of all products to the current vat percentage
        $currentVatPercentage = TaxTool::getCurrentVatPercentage();
        $products = $this->modelManager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $product->setTax($currentVatPercentage);
        }
        $this->modelManager->flush();

        // Pull back shopware prices to products
        /** @var Article $article */
        foreach ($articles as $article) {
            $details = $article->getDetails();

        }
    }

    protected function getCustomerGroupRepository()
    {
        return $this->customerGroupRepository ?? $this->customerGroupRepository = $this->modelManager->getRepository(Group::class);
    }
}