<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareAssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareOptionMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwarePriceMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\SupplierTool;
use MxcDropshipInnocigs\Toolbox\Shopware\TaxTool;
use Shopware\Components\Api\Resource\Article as ArticleResource;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Configurator\Set;
use Shopware\Models\Article\Detail;
use Shopware\Models\Plugin\Plugin;

class ShopwareMapper
{
    /** @var array $associatedArticles */
    protected $associatedArticles;

    /** @var array $createdArticles */
    protected $createdArticles;

    /** @var ShopwareAssociatedArticlesMapper $associatedArticlesMapper */
    protected $associatedArticlesMapper;

    /** @var ShopwareCategoryMapper $categoryMapper */
    protected $categoryMapper;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var ShopwareOptionMapper $optionMapper */
    protected $optionMapper;

    /** @var ApiClient $client */
    protected $client;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ShopwareImageMapper $imageMapper */
    protected $imageMapper;

    /** @var bool */
    protected $dropshippersCompanionPresent;

    /** @var ShopwarePriceMapper $priceTool */
    protected $priceTool;

    /**
     * ShopwareMapper constructor.
     *
     * @param ModelManager $modelManager
     * @param ShopwareOptionMapper $optionMapper
     * @param ShopwareImageMapper $imageMapper
     * @param ShopwareCategoryMapper $categoryMapper
     * @param ShopwarePriceMapper $priceTool
     * @param ShopwareAssociatedArticlesMapper $associatedArticlesMapper
     * @param ApiClient $client
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ShopwareOptionMapper $optionMapper,
        ShopwareImageMapper $imageMapper,
        ShopwareCategoryMapper $categoryMapper,
        ShopwarePriceMapper $priceTool,
        ShopwareAssociatedArticlesMapper $associatedArticlesMapper,
        ApiClient $client,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->optionMapper = $optionMapper;
        $this->imageMapper = $imageMapper;
        $this->categoryMapper = $categoryMapper;
        $this->associatedArticlesMapper = $associatedArticlesMapper;
        $this->client = $client;
        $this->log = $log;
        $this->dropshippersCompanionPresent = $this->validateDropshippersCompanion();
        $this->priceTool = $priceTool;
    }

    public function setArticleAcceptedState(array $icArticles, bool $accepted)
    {
        /** @var Article $icArticle */
        foreach ($icArticles as $icArticle) {
            $icArticle->setAccepted($accepted);
            if ($accepted) continue;
            $this->setShopwareArticleActive($icArticle);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }



    public function createArticles (array $icArticles) {

    }

    /**
     * Main entry point if the $active, $accepted or $linked state of a list of
     * InnoCigs articles changes.
     *
     * $accepted === false  the associated Shopware article gets deactivated
     *                      (articles which are not accepted do not get created
     *                      regardless of the other settings)
     *
     * $active === true     the Shopware article gets created/updated and activated
     * $active === false    the associated Shopware article gets deactivated without
     *                      getting updated
     *
     * $linked === true     the Shopware article gets created
     *
     * $createArticlesNotActive === true    create article even if it is not $active
     * $createArticlesNotActive === false   don't create articles which are not $active
     *
     * @param array $icArticles
     * @param bool $createArticlesNotActive
     */
    public function processStateChangesArticleList(array $icArticles, bool $createArticlesNotActive = false)
    {
        $this->createdArticles = [];
        $activeArticles = [];
        /** @var Article $icArticle */
        foreach ($icArticles as $icArticle) {
            if (! $this->setShopwareArticle($icArticle, $createArticlesNotActive)) {
                $this->setShopwareArticleActive($icArticle);
                continue;
            }
            $icNumber = $icArticle->getIcNumber();
            $activeArticles[$icNumber] = $activeArticles[$icNumber] ?? $icArticle;
        }

        $this->processAssociatedArticles($createArticlesNotActive, $activeArticles);

        // Update all articles with similar or related articles referencing articles
        // that we just created.
        if (! empty($this->createdArticles)) {
            $this->updateArticleLinks($this->createdArticles);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    public function updateShopwareArticles(array $icArticles)
    {
        $this->processStateChangesArticleList($icArticles, false);
    }

    /**
     * @see processStateChangesArticleList
     *
     * @param Article $icArticle
     * @param bool $createArticlesNotActive
     * @return bool
     *
     */
    public function processStateChangesArticle(Article $icArticle, bool $createArticlesNotActive = false)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->processStateChangesArticleList([$icArticle], $createArticlesNotActive);
        return $icArticle->isActive();
    }

    /**
     * @param bool $createArticlesNotActive
     * @param array $activeArticles
     */
    public function processAssociatedArticles(bool $createArticlesNotActive, array $activeArticles)
    {
        $associatedArticles = $this->associatedArticlesMapper->getAssociatedArticles($activeArticles);

        /** @var Article $icArticle */
        foreach ($associatedArticles as $icArticle) {
            if ($this->setShopwareArticle($icArticle, $createArticlesNotActive)) {
                $activeArticles[$icArticle->getIcNumber()] = $icArticle;
            }
            $this->setShopwareArticleActive($icArticle);
        }

        foreach ($activeArticles as $icArticle) {
            $this->associatedArticlesMapper->setRelatedArticles($icArticle);
            $this->associatedArticlesMapper->setSimilarArticles($icArticle);
            $this->setShopwareArticleActive($icArticle);
        }
    }

    /**
     * Create/Update the Shopware article associated to the active InnoCigs article.
     *
     * @param Article $icArticle
     * @param bool $allowCreate
     * @return bool
     */
    protected function setShopwareArticle(Article $icArticle, bool $allowCreate): bool
    {
        if (! $icArticle->isValid()) {
            $icArticle->setActive(false);
            return false;
        }

        $swArticle = $icArticle->getArticle();
        $created = false;

        if ($swArticle === null) {
            if (! $allowCreate) return false;

            // Create Shopware Article
            $swArticle = new ShopwareArticle();
            $this->modelManager->persist($swArticle);
            $icArticle->setArticle($swArticle);
            $icArticle->setLinked(true);
            $this->createdArticles[] = $icArticle->getIcNumber();
            $created = true;
        }

        $this->removeDetachedShopwareDetails($icArticle);

        $set = $this->optionMapper->createConfiguratorSet($icArticle);
        $swArticle->setConfiguratorSet($set);

        $this->setShopwareArticleProperties($icArticle, $created);
        $this->setShopwareDetails($icArticle);

        ShopwarePriceMapper::setReferencePrice($icArticle);

        // We have to flush each article in order to get the newly created categories
        // pushed to the database.

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();

        $this->imageMapper->setArticleImages($icArticle);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();

        return true;
    }

    /**
     * Create/Update all Shopware details associated to the InnoCogs article's
     * variants.
     *
     * @param Article $icArticle
     */
    protected function setShopwareDetails(Article $icArticle): void
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $icVariants = $icArticle->getVariants();

        $isMainDetail = true;
        /** @var Variant $icVariant */
        foreach ($icVariants as $icVariant) {
            $swDetail = $this->setShopwareDetail($icVariant);
            if ($swDetail === null) continue;
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
    protected function setShopwareDetail(Variant $icVariant)
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
        if (! $icVariant->isValid()) return null;

        $icArticle = $icVariant->getArticle();
        $swArticle = $icArticle->getArticle();

        if (! $swArticle) return null;

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
        $this->priceTool->setRetailPrices($icVariant);
        $swDetail->setShippingTime(5);
        $swDetail->setLastStock(0);

        // Note: shopware options were added non persistently to variants when configurator set was created
        $swDetail->setConfiguratorOptions(new ArrayCollection($icVariant->getShopwareOptions()));

        return $swDetail;
    }

    /**
     * Set the properties of the Shopware article associated to the given InnoCigs article.
     *
     * @param Article $icArticle
     * @param bool $force true: overwrite
     */
    protected function setShopwareArticleProperties(Article $icArticle, bool $force)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        // update description if not already set or if force is set
        $probe = $swArticle->getDescriptionLong();
        if ($force || !$probe || $probe === '') {
            $swArticle->setDescriptionLong($icArticle->getDescription());
        }

        $probe = $swArticle->getDescription();
        if ($force || !$probe || $probe === '') {
            $swArticle->setDescription('');
        }

        $probe = $swArticle->getKeywords();
        if ($force || !$probe || $probe === '') {
            $swArticle->setKeywords('');
        }

        $probe = $swArticle->getMetaTitle();
        if ($force || !$probe || $probe === '') {
            $metaTitle = 'Vapee.de: ' . preg_replace('~\(\d+ Stück pro Packung\)~', '', $icArticle->getName());
            $swArticle->setMetaTitle($metaTitle);
        }

        $probe = $swArticle->getName();
        if ($force || !$probe || $probe === '') {
            $swArticle->setName($icArticle->getName());
        }

        $swArticle->setTax(TaxTool::getTax($icArticle->getTax()));
        $swArticle->setSupplier(SupplierTool::getSupplier($icArticle->getSupplier()));
        $this->categoryMapper->map($icArticle);
    }

    /**
     * Set the properties of the Shopware detail associated to the given InnoCigs variant.
     *
     * @param Variant $icVariant
     */
    public function setShopwareDetailProperties(Variant $icVariant)
    {
        $swDetail = $icVariant->getDetail();
        if (! $swDetail) return;

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
     * Delete the detached Detail records of the Shopware article associated to the
     * given InnoCigs article from the database. A Detail record is detached if the
     * InnoCigs article does not have a variant associated to the particular Detail
     * record or if the variant is not accepted.
     *
     * @param Article $icArticle
     */
    public function removeDetachedShopwareDetails(Article $icArticle)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $detailsToDelete = $this->modelManager->getRepository(Article::class)->getDetailsNotAccepted($icArticle);

        /** @var Detail $detailToDelete */
        foreach ($detailsToDelete as $detailToDelete) {
            /** @todo: Main detail treatment */
            $this->removeShopwareDetail($detailToDelete);
        }
    }

    /**
     * Set the shopware article active state to according to the $active state
     * of the given InnoCigs article. Can modify the dropship active state of the
     * Shopware Details. If the InnoCigs article is active, dropship gets
     * enabled for all active Shopware details and disabled for non active
     * Shopware details. If the article is not active, dropship gets disabled
     * for all Shopware details.
     *
     * If the InnoCigs article is not valid any longer or there is no corresponding
     * Shopware article, the InnoCigs article gets deactivated.
     *
     * @param Article $icArticle
     */
    public function setShopwareArticleActive(Article $icArticle)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        $active = $icArticle->isValid() && $icArticle->isActive() && $swArticle !== null;

        $icArticle->setActive($active);

        $icVariants = $icArticle->getVariants();
        /** @var Variant $icVariant */
        foreach ($icVariants as $icVariant) {
            $this->setShopwareDetailActive($icVariant, $active);
        }

        if ($swArticle) {
            $swArticle->setActive($active);
        }
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

        if (! $swDetail) return;

        $swDetail->setActive($icVariant->isValid());

        if (! $this->dropshippersCompanionPresent) return;

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
        $attribute->setDcIcInstock($this->client->getStockInfo($icVariant->getIcNumber()));
    }

    public function removeVariant(Variant $icVariant)
    {
        $swDetail = $icVariant->getDetail();
        if ($swDetail && $swDetail->getKind() === 2) {
            $this->modelManager->remove($swDetail);
        }
    }

    public function removeArticle(Article $icArticle) {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $configuratorSetName = 'mxc-set-' . $icArticle->getIcNumber();
        $set = $this->modelManager->getRepository(Set::class)->findOneBy(['name' => $configuratorSetName]);
        if ($set) {
            $this->modelManager->remove($set);
        }

        $articleResource = new ArticleResource();
        $articleResource->setManager($this->modelManager);
        /** @noinspection PhpUnhandledExceptionInspection */
    }

    public function removeShopwareDetail(Detail $swDetail)
    {
        if ($swDetail->getKind() == 1) {
            $articleResource = new ArticleResource();
            $articleResource->setManager($this->modelManager);
            /** @noinspection PhpUnhandledExceptionInspection */
            $articleResource->delete($swDetail->getArticle()->getId());
        } else {
            Shopware()->Models()->remove($swDetail);
        }
    }

    /**
     * Update the related and similar article lists of all Shopware articles
     * where the corresponding icArticle has related and similar articles from
     * the given $icArticles array.
     *
     * @param array $icArticles
     */
    protected function updateArticleLinks(array $icArticles) {
        if (count($icArticles) === 0) return;

        $repository = $this->modelManager->getRepository(Article::class);

        $articlesWithRelatedNewArticles = $repository->getHavingRelatedArticles($icArticles);
        foreach ($articlesWithRelatedNewArticles as $icArticle) {
            $this->associatedArticlesMapper->setRelatedArticles($icArticle);
        }

        $articlesWithSimilarNewArticles = $repository->getHavingSimilarArticles($icArticles);
        foreach ($articlesWithSimilarNewArticles as $icArticle) {
            $this->associatedArticlesMapper->setSimilarArticles($icArticle);
        }
    }

    /**
     * Check if the Dropshipper's Companion for InnoCigs Shopware plugin is installed or not.
     * If installed, check if the required APIs provided by the companion plugin are present.
     *
     * @return bool
     */
    protected function validateDropshippersCompanion(): bool
    {
        $className = 'Shopware\Models\Attribute\Article';
        if (null === $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs'])
            || ! (method_exists($className, 'setDcIcOrderNumber')
                && method_exists($className, 'setDcIcArticleName')
                && method_exists($className, 'setDcIcPurchasingPrice')
                && method_exists($className, 'setDcIcRetailPrice')
                && method_exists($className, 'setDcIcActive')
                && method_exists($className, 'setDcIcInstock'))
        ) {
            $this->log->warn('Can not prepare articles for dropship orders. Dropshipper\'s Companion is not installed.');
            return false;
        };
        return true;
    }

}
