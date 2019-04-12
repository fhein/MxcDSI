<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Detail;
use Shopware\Models\Plugin\Plugin;

class ShopwareDetailMapper
{
    /** @var LoggerInterface $log */
    protected $log;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ShopwarePriceMapper $priceMapper */
    protected $priceMapper;

    /** @var ApiClient */
    protected $apiClient;

    protected $dropshippersCompanionPresent;

    public function __construct(ModelManager $modelManager, ShopwarePriceMapper $priceMapper, ApiClient $apiClient, LoggerInterface $log)
    {
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->priceMapper = $priceMapper;
        $this->apiClient = $apiClient;
    }

    /**
     * Create/Update all Shopware details associated to the InnoCogs article's
     * variants.
     *
     * @param Article $icArticle
     */
    public function map(Article $icArticle): void
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (!$swArticle) {
            return;
        }

        $icVariants = $icArticle->getVariants();

        $isMainDetail = true;
        /** @var Variant $icVariant */
        foreach ($icVariants as $icVariant) {
            $swDetail = $this->setShopwareDetail($icVariant);
            if ($swDetail === null) {
                continue;
            }
            $swDetail->setKind(2);
            if ($isMainDetail) {
                $swDetail->setKind(1);
                $swArticle->setMainDetail($swDetail);
                $swArticle->setAttribute($swDetail->getAttribute());
                $isMainDetail = false;
            }
        }
    }

    /**
     * Set the properties of the Shopware detail associated to the given InnoCigs variant.
     * If the detail does not exist, it will be created.
     *
     * @param Variant $icVariant
     * @return Detail|null
     */
    public function setShopwareDetail(Variant $icVariant)
    {
        $swDetail = $icVariant->getDetail();

        if ($swDetail) {
            // Update existing detail
            $this->setShopwareDetailProperties($icVariant);
            $configuratorOptions = $swDetail->getConfiguratorOptions();
            $configuratorOptions->clear();
            $swDetail->setConfiguratorOptions(new ArrayCollection($icVariant->getShopwareOptions()));
            return $swDetail;
        }

        // Create new detail if this variant is valid
        if (!$icVariant->isValid()) {
            return null;
        }

        $icArticle = $icVariant->getArticle();
        $swArticle = $icArticle->getArticle();

        if (!$swArticle) {
            return null;
        }

        $swDetail = new Detail();
        $this->modelManager->persist($swDetail);
        // The next two settings have to be made upfront because the later code relies on these
        $icVariant->setDetail($swDetail);
        $swDetail->setArticle($swArticle);

        // The class \Shopware\Models\Attribute\Article ist part of the Shopware attribute system.
        // It gets (re)generated automatically by Shopware core, when attributes are added/removed
        // via the attribute crud service. It is located in \var\cache\production\doctrine\attributes.
        $attribute = new \Shopware\Models\Attribute\Article();
        $swDetail->setAttribute($attribute);

        $this->setShopwareDetailProperties($icVariant);

        // All valid details are marked active
        $swDetail->setActive(true);

        // set next three properties only on detail creation
        $this->priceMapper->setRetailPrices($icVariant);
        $swDetail->setShippingTime(5);
        $swDetail->setLastStock(0);

        // Note: shopware options were added non persistently to variants when configurator set was created
        $swDetail->setConfiguratorOptions(new ArrayCollection($icVariant->getShopwareOptions()));

        return $swDetail;
    }

    /**
     * Set the properties of the Shopware detail associated to the given InnoCigs variant.
     *
     * @param Variant $icVariant
     */
    public function setShopwareDetailProperties(Variant $icVariant)
    {
        $swDetail = $icVariant->getDetail();
        if (!$swDetail) {
            return;
        }

        $swDetail->setNumber($icVariant->getNumber());
        $swDetail->setEan($icVariant->getEan());
        $purchasePrice = floatval(str_replace(',', '.', $icVariant->getPurchasePrice()));
        $swDetail->setPurchasePrice($purchasePrice);

        $attribute = $swDetail->getAttribute();
        $icArticle = $icVariant->getArticle();

        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiBrand($icArticle->getBrand());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiSupplier($icArticle->getSupplier());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiFlavor($icArticle->getFlavor());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiMaster($icArticle->getIcNumber());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiType($icArticle->getType());
    }

    /**
     * Set the Shopware detail attributes for the dropship plugin.
     *
     * @param Variant $icVariant
     * @param bool $active
     */
    public function setShopwareDetailActive(Variant $icVariant, bool $active)
    {
        $swDetail = $icVariant->getDetail();

        $active = $active && $icVariant->isValid() && $swDetail !== null;
        $icVariant->setActive($active);

        if (!$swDetail) {
            return;
        }

        $swDetail->setActive($icVariant->isValid());

        if (!$this->validateDropshippersCompanion()) {
            return;
        }

        $attribute = $swDetail->getAttribute();
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcActive($active);
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcOrderNumber($icVariant->getIcNumber());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcArticleName($icVariant->getArticle()->getName());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcPurchasingPrice($icVariant->getPurchasePrice());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcRetailPrice($icVariant->getRecommendedRetailPrice());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcInstock($this->apiClient->getStockInfo($icVariant->getIcNumber()));
    }

    /**
     * Check if the Dropshipper's Companion for InnoCigs Shopware plugin is installed or not.
     * If installed, check if the required APIs provided by the companion plugin are present.
     *
     * @return bool
     */
    public function validateDropshippersCompanion(): bool
    {
        if (! is_bool($this->dropshippersCompanionPresent)) {
            $className = 'Shopware\Models\Attribute\Article';
            if (null === $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs'])
                || !(method_exists($className, 'setDcIcOrderNumber')
                    && method_exists($className, 'setDcIcArticleName')
                    && method_exists($className, 'setDcIcPurchasingPrice')
                    && method_exists($className, 'setDcIcRetailPrice')
                    && method_exists($className, 'setDcIcActive')
                    && method_exists($className, 'setDcIcInstock'))
            ) {
                $this->log->warn('Can not prepare articles for dropship orders. Dropshipper\'s Companion is not installed.');
                $this->dropshippersCompanionPresent = false;
            } else {
                $this->dropshippersCompanionPresent = true;
            }
        };
        return $this->dropshippersCompanionPresent;
    }
}