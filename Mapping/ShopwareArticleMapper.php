<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;
use MxcDropshipInnocigs\Toolbox\Shopware\SupplierTool;
use MxcDropshipInnocigs\Toolbox\Shopware\TaxTool;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Detail;
use Shopware\Models\Category\Category;
use Shopware\Models\Plugin\Plugin;
use Zend\Config\Config;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class ShopwareArticleMapper
{
    /** @var array $associatedArticles */
    protected $associatedArticles;

    /** @var array $createdArticles */
    protected $createdArticles;

    /**
     * @var LoggerInterface $log
     */
    protected $log;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @var ShopwareOptionMapper $optionMapper
     */
    protected $optionMapper;

    /**
     * @var ApiClient $client
     */
    protected $client;
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;
    /**
     * @var MediaTool $mediaTool
     */
    protected $mediaTool;

    /** @var bool */
    protected $dropshippersCompanionPresent;

    /** @var PriceMapper $priceTool */
    protected $priceTool;

    /**
     * ShopwareArticleMapper constructor.
     *
     * @param ModelManager $modelManager
     * @param ShopwareOptionMapper $optionMapper
     * @param MediaTool $mediaTool
     * @param PriceMapper $priceTool
     * @param ApiClient $client
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ShopwareOptionMapper $optionMapper,
        MediaTool $mediaTool,
        PriceMapper $priceTool,
        ApiClient $client,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->optionMapper = $optionMapper;
        $this->mediaTool = $mediaTool;
        $this->client = $client;
        $this->config = $config;
        $this->log = $log;
        $this->dropshippersCompanionPresent = $this->validateDropshippersCompanion();
        $this->priceTool = $priceTool;
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
    protected function processAssociatedArticles(bool $createArticlesNotActive, array $activeArticles): void
    {
        $this->associatedArticles = [];
        foreach ($activeArticles as $icArticle) {
            $this->prepareAssociatedArticles($icArticle);
        }

        foreach ($this->associatedArticles as $icArticle) {
            if ($this->setShopwareArticle($icArticle, $createArticlesNotActive)) {
                $activeArticles[$icArticle->getIcNumber()] = $icArticle;
            }
            $this->setShopwareArticleActive($icArticle);
        }

        foreach ($activeArticles as $icArticle) {
            $this->setRelatedArticles($icArticle);
            $this->setSimilarArticles($icArticle);
            $this->setShopwareArticleActive($icArticle);
        }
    }

    /**
     * Fill the $this->associatedArticles recursively
     *
     * @param Article $icArticle
     */
    protected function prepareAssociatedArticles(Article $icArticle) {
        // exit recursion if $icArticle is registered already
        if ($this->associatedArticles[$icArticle->getIcNumber()]) return;

        $this->prepareAssociatedArticlesCollection(
            $icArticle->getRelatedArticles(),
            $icArticle->getCreateRelatedArticles(),
            $icArticle->getActivateCreatedRelatedArticles()
        );

        $this->prepareAssociatedArticlesCollection(
            $icArticle->getSimilarArticles(),
            $icArticle->getCreateSimilarArticles(),
            $icArticle->getActivateCreatedSimilarArticles()
        );
    }

    /**
     * @param Collection $icArticles
     * @param bool $createAssociated
     * @param bool $activateAssociated
     */
    protected function prepareAssociatedArticlesCollection(
        Collection $icArticles,
        bool $createAssociated,
        bool $activateAssociated
    ) {
        /** @var Article $article */
        foreach ($icArticles as $article) {
            $isNew = $article->getArticle() === null;
            if (! $createAssociated && $isNew) {
                continue;
            }
            if ($isNew) {
                $article->setActive($activateAssociated);
            }
            $this->associatedArticles[$article->getIcNumber()] = $article;

            // Recursion
            $this->prepareAssociatedArticles($article);
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

        PriceMapper::setReferencePrice($icArticle);

        // We have to flush each article in order to get the newly created categories
        // pushed to the database.

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();

        $this->mediaTool->setArticleImages($icArticle);
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

        // All new valid details get marked active
        $swDetail->setActive($icArticle->isActive());

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
        $this->setCategories($icArticle);
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
     * Remove a Shopware detail record from the database. Deletes the images and
     * translations also.
     *
     * @param Detail $swDetail
     */
    protected function removeShopwareDetail(Detail $swDetail) {
        $repository = $this->modelManager->getRepository(ShopwareArticle::class);
        $detailId = $swDetail->getId();

        $repository->getRemoveImageQuery($detailId)->execute();
        $repository->getRemoveVariantTranslationsQuery($detailId)->execute();
        $this->modelManager->remove($swDetail);
    }

    /**
     * Delete the detached Detail records of the Shopware article associated to the
     * given InnoCigs article from the database. A Detail record is detached if the
     * InnoCigs article does not have a variant associated to the particular Detail
     * record or if the variant is not accepted.
     *
     * @param Article $icArticle
     */
    protected function removeDetachedShopwareDetails(Article $icArticle)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $swDetails = $swArticle->getDetails();
        if ($swDetails->isEmpty()) return;

        $deletedDetails = [];

        /** @var Detail $swDetail */
        foreach ($swDetails as $swDetail) {
            $deletedDetails[$swDetail->getNumber()] = $swDetail;
        }

        /** @var Variant $icVariant */
        $icVariants = $icArticle->getVariants();
        foreach ($icVariants as $icVariant) {
            if (! $icVariant->isValid()) continue;
            unset($deletedDetails[$icVariant->getNumber()]);
        }

        foreach ($deletedDetails as $deletedDetail) {
            $this->removeShopwareDetail($deletedDetail);
        }
    }

    /**
     * Set the shopware article active state to according to the $active state
     * of the given InnoCigs article. Can modify the active state of the
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
     * Set the Shopware detail's active state
     *
     * @param Variant $icVariant
     * @param bool $active
     */
    public function setShopwareDetailActive(Variant $icVariant, bool $active)
    {
        $swDetail = $icVariant->getDetail();
        $active = $active && $icVariant->isValid() && $swDetail !== null;
        $this->setDropship($icVariant, $active);
        $icVariant->setActive($active);
        if ($swDetail) {
            $swDetail->setActive($active);
        }
    }

    /**
     * Set the Shopware detail attributes for the dropship plugin.
     *
     * @param Variant $icVariant
     * @param bool $active
     */
    protected function setDropship(Variant $icVariant, bool $active)
    {
        if (! $this->dropshippersCompanionPresent) return;

        $swDetail = $icVariant->getDetail();
        if (! $swDetail) return;

        $attribute = $swDetail->getAttribute();
        if (! $attribute) return;

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

    /**
     * Create and return a new sub-category for a given Shopware category.
     *
     * @param Category $parent
     * @param string $name
     * @return Category
     */
    protected function createCategory(Category $parent, string $name)
    {
        $child = new Category();
        $this->modelManager->persist($child);
        $child->setName($name);
        $child->setParent($parent);
        $child->setChanged();
        if ($parent->getArticles()->count() > 0) {
            /** @var ShopwareArticle $article */
            foreach ($parent->getArticles() as $article) {
                $article->removeCategory($parent);
                $article->addCategory($child);
            }
            $parent->setChanged();
        }
        return $child;
    }

    /**
     * Get a Shopware category object for a given category path (example: E-Zigaretten > Aspire)
     * All categories of the path are created if they do not exist. The category path gets created
     * below a given root category. If no root category is provided, the path will be added below
     * the Shopware root category.
     *
     * @param string $path
     * @param Category|null $root
     * @return Category
     */
    protected function getCategory(string $path, Category $root = null)
    {
        $repository = $this->modelManager->getRepository(Category::class);
        /** @var Category $parent */
        $parent = ($root !== null) ? $root : $repository->findOneBy(['parentId' => null]);
        $nodes = explode(' > ', $path);
        foreach ($nodes as $categoryName) {
            $child = $repository->findOneBy(['name' => $categoryName, 'parentId' => $parent->getId()]);
            $parent = $child ?? $this->createCategory($parent, $categoryName);
        }
        return $parent;
    }

    /**
     * Add Shopware categories provided as a list of '#!#' separated category paths
     * to the Shopware article associated to the given InnoCigs article.
     *
     * @param Article $icArticle
     */
    protected function setCategories(Article $icArticle)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $root = $this->config->get('root_category', 'Deutsch');
        $rootCategory = $this->getCategory($root);
        $catgories = explode(MXC_DELIMITER_L1, $icArticle->getCategory());
        foreach ($catgories as $category) {
            // if ($icArticle->getName() === 'SC - Base - 100 ml, 0 mg/ml') xdebug_break();
            $this->log->debug('Getting category for article '. $icArticle->getName());
            $swCategory = $this->getCategory($category, $rootCategory);
            $swArticle->addCategory($swCategory);
            $swCategory->setChanged();
        }
    }

    /**
     * For a given collection of InnoCigs articles return a collection of all associated Shopware articles.
     *
     * @param Collection $icArticles
     * @return ArrayCollection
     */
    protected function getShopwareArticles(Collection $icArticles): ArrayCollection
    {
        $swArticles = [];
        foreach ($icArticles as $icArticle) {
            $swArticle = $icArticle->getArticle();
            if ($swArticle !== null) {
                $swArticles[] = $swArticle;
            }
        }
        return new ArrayCollection($swArticles);
    }

    /**
     * Add all articles of the given Shopware article collection to the target collection.
     * No duplicates.
     *
     * @param Collection $swArticles
     * @param Collection $collection
     */
    protected function addArticlesToCollection(Collection $swArticles, Collection $collection)
    {
        foreach ($swArticles as $article) {
            if (!$collection->contains($article)) {
                $collection->add($article);
            }
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
            $this->setRelatedArticles($icArticle);
        }

        $articlesWithSimilarNewArticles = $repository->getHavingSimilarArticles($icArticles);
        foreach ($articlesWithSimilarNewArticles as $icArticle) {
            $this->setSimilarArticles($icArticle);
        }
    }

    /**
     * Set the related articles of a Shopware article according to the settings of the InnoCigs article.
     * If the $replace flag is true, the related articles of the Shopware article will be replaced. If the
     * $replace flag is false, new related articles will be added, if any.
     *
     * @param Article $icArticle
     * @param bool $replace true: replace related articles, false: add related articles
     */
    protected function setRelatedArticles(Article $icArticle, bool $replace = false)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $related = $swArticle->getRelated();
        if ($replace) $related->clear();
        $relatedArticles = $this->getShopwareArticles($icArticle->getRelatedArticles());
        $this->addArticlesToCollection($relatedArticles, $related);
    }

    /**
     * Set the similar articles of a Shopware article according to the settings of the InnoCigs article.
     * If the $replace flag is true, the similar articles of the Shopware article will be replaced. If the
     * $replace flag is false, new similar articles will be added, if any.
     *
     * @param Article $icArticle
     * @param bool $replace true: replace related articles, false: add related articles
     */
    public function setSimilarArticles(Article $icArticle, bool $replace = false)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $similar = $swArticle->getSimilar();
        if ($replace) $similar->clear();
        $similarArticles = $this->getShopwareArticles($icArticle->getSimilarArticles());
        $this->addArticlesToCollection($similarArticles, $similar);
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
