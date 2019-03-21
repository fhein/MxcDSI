<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Article\Unit;
use Shopware\Models\Category\Category;
use Shopware\Models\Customer\Group;
use Shopware\Models\Plugin\Plugin;
use Shopware\Models\Tax\Tax;
use Zend\Config\Config;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class ArticleMapper
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
     * @var ArticleOptionMapper $optionMapper
     */
    protected $optionMapper;

    /**
     * @var ImportMapper $client
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

    protected $customerGroups;

    /**
     * ArticleMapper constructor.
     *
     * @param ModelManager $modelManager
     * @param ArticleOptionMapper $optionMapper
     * @param MediaTool $mediaTool
     * @param ImportMapper $client
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ArticleOptionMapper $optionMapper,
        MediaTool $mediaTool,
        ImportMapper $client,
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
        $customerGroups = $this->modelManager->getRepository(Group::class)->findAll();
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroups[$customerGroup->getKey()] = $customerGroup;
        }
    }

    /**
     * Main entry point if the $active state of an InnoCigs article changes.
     * If $active === true, creates/updates the Shopware article associated to
     * the given InnoCigs article.
     * If $active === false the Shopware article gets disabled without getting
     * updated.
     *
     * @param Article $icArticle
     * @return ShopwareArticle|null
     */
    public function handleActiveStateChange(Article $icArticle)
    {
        $swArticle = null;
        $this->createdArticles = [];

        if ($icArticle->isActive()) {
            // update or create article, can modify icArticle's active state
            $swArticle = $this->setShopwareArticle($icArticle);
        }

        if ($icArticle->isActive()) {
            // Recursively build a list of all articles associated to this article
            // which need to get created or activated
            $this->associatedArticles = [];
            $this->prepareAssociatedArticles($icArticle);

            foreach ($this->associatedArticles as $article) {
                $this->setShopwareArticle($article);
                $this->setShopwareArticleActive($article);
            }

            // All related and similar articles have been created according to
            // the configuration of the $icArticle
            $this->setRelatedArticles($icArticle);
            $this->setSimilarArticles($icArticle);
        }

        $this->setShopwareArticleActive($icArticle);

        // Update all articles with similar or related articles referencing articles
        // that we just created.
        if (! empty($this->createdArticles)) {
            $this->updateArticleLinks($this->createdArticles);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
        return $swArticle;
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

        $articlesWithRelatedNewArticles = $repository->getAllHavingRelatedArticles($icArticles);
        foreach ($articlesWithRelatedNewArticles as $icArticle) {
            $this->setRelatedArticles($icArticle);
        }

        $articlesWithSimilarNewArticles = $repository->getAllHavingSimilarArticles($icArticles);
        foreach ($articlesWithSimilarNewArticles as $icArticle) {
            $this->setSimilarArticles($icArticle);
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
            $icArticle->getActivateRelatedArticles()
        );

        $this->prepareAssociatedArticlesCollection(
            $icArticle->getSimilarArticles(),
            $icArticle->getCreateSimilarArticles(),
            $icArticle->getActivateSimilarArticles()
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
            if (! $createAssociated && ! $article->getArticle()) {
                $article->setActive(false);
                continue;
            }
            if ($activateAssociated) {
                $article->setActive(true);
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
     * @return ShopwareArticle|null
     */
    protected function setShopwareArticle(Article $icArticle): ?ShopwareArticle
    {
        $swArticle = $icArticle->getArticle();
        $create = ($swArticle === null);

        // Only create active articles
        if ($create && ! $icArticle->isActive()) return null;

        // Deactivate article if it is not accepted any longer
        if ( ! $icArticle->isValid()) {
            $icArticle->setActive(false);
            return $swArticle;
        }

        if ($create) {
            // Create Shopware Article
            $swArticle = new ShopwareArticle();
            $this->modelManager->persist($swArticle);
            $icArticle->setArticle($swArticle);
            $this->createdArticles[] = $icArticle->getIcNumber();
        }

        $this->removeObsoleteShopwareDetails($icArticle);

        $set = $this->optionMapper->createConfiguratorSet($icArticle);
        $swArticle->setConfiguratorSet($set);

        $this->setShopwareArticleProperties($icArticle, $create);
        $this->setShopwareDetails($icArticle);

        $this->mediaTool->setArticleImages($icArticle);
        $this->setReferencePrice($icArticle);

        // We have to flush each article in order to get the newly created categories
        // pushed to the database.

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();

        return $swArticle;
    }

    /**
     * Create/Update all Shopware details associated to the InnoCogs article's
     * variants.
     *
     * @param Article $icArticle
     */
    protected function setShopwareDetails(Article $icArticle): void
    {
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $icVariants = $icArticle->getVariants();

        $isMainDetail = true;
        /** @var Variant $icVariant */
        foreach ($icVariants as $icVariant) {
            $swDetail = $this->setShopwareDetail($icVariant);
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
            $this->setShopwareDetailProperties($icVariant, false);
            $configuratorOptions = $swDetail->getConfiguratorOptions();
            $configuratorOptions->clear();
            // Next line is necessary to avoid Doctrine duplicte relation exception
            $swDetail->getConfiguratorOptions()->clear();
            $swDetail->setConfiguratorOptions(new ArrayCollection($icVariant->getShopwareOptions()));
            return $swDetail;
        }

        // Create new detail
        $swArticle = $icVariant->getArticle()->getArticle();
        if (! $swArticle) return null;

        $detail = new Detail();
        $this->modelManager->persist($detail);
        // The next two settings have to be made upfront because the later code relies on these
        $icVariant->setDetail($detail);
        $detail->setArticle($swArticle);

        // The class \Shopware\Models\Attribute\Article ist part of the Shopware attribute system.
        // It gets (re)generated automatically by Shopware core, when attributes are added/removed
        // via the attribute crud service. It is located in \var\cache\production\doctrine\attributes.
        $attribute = new \Shopware\Models\Attribute\Article();
        $detail->setAttribute($attribute);

        $this->setShopwareDetailProperties($icVariant,  true);
        $detail->setActive(false);

        // set retail price only if detail gets created
        $this->setRetailPrice($icVariant);

        // Note: shopware options were added non persistently to variants when configurator set was created
        $detail->setConfiguratorOptions(new ArrayCollection($icVariant->getShopwareOptions()));

        return $detail;
    }

    /**
     * Set the properties of the Shopware article associated to the given InnoCigs article.
     *
     * @param Article $icArticle
     * @param bool $force true: overwrite
     */
    protected function setShopwareArticleProperties(Article $icArticle, bool $force)
    {
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

        $swArticle->setTax($this->getTax($icArticle->getTax()));
        $swArticle->setSupplier($this->getSupplier($icArticle));
        $this->setCategories($icArticle);
    }

    /**
     * Set the properties of the Shopware detail associated to the given InnoCigs variant.
     *
     * @param Variant $icVariant
     * @param bool $force           true: overwrite
     */
    protected function setShopwareDetailProperties(Variant $icVariant, bool $force)
    {
        $swDetail = $icVariant->getDetail();
        $icArticle = $icVariant->getArticle();

        $swDetail->setNumber($icVariant->getNumber());
        $swDetail->setEan($icVariant->getEan());
        $swDetail->setPurchasePrice($icVariant->getPurchasePrice());

        $attribute = $swDetail->getAttribute();
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiBrand($icArticle->getBrand());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiSupplier($icArticle->getSupplier());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiFlavor($icArticle->getFlavor());

        $probe = $swDetail->getShippingTime();
        if ($force || ! $probe ) {
            $swDetail->setShippingTime(5);
        }

        $probe = $swDetail->getLastStock();
        if ($force || ! $probe) {
            $swDetail->setLastStock(0);
        }

        // The next properties are nullable. As long as we do not provide
        // default values we ignore them.

//        $probe = $swDetail->getStockMin();
//        if ($force || ! $probe) {
//            $swDetail->setStockMin(null);
//        }
//
//        $probe = $swDetail->getSupplierNumber();
//        if ($force || ! $probe) {
//            $swDetail->setSupplierNumber(null);
//        }
//
//        $probe = $swDetail->getAdditionalText();
//        if ($force || ! $probe) {
//            $swDetail->setAdditionalText(null);
//        }
//
//        $probe = $swDetail->getPackUnit();
//        if ($force || ! $probe) {
//            $swDetail->setPackUnit(null);
//        }
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
     * Delete the obsolete detail records of the Shopware article associated to the
     * given InnoCigs article from the database. A Detail record is obsolete if the
     * InnoCigs article does not have a variant associated to the particular detail record.
     *
     * @param Article $icArticle
     */
    protected function removeObsoleteShopwareDetails(Article $icArticle)
    {
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
            $number = $icVariant->getNumber();
            if ($deletedDetails[$number]) {
                unset($deletedDetails[$number]);
            }
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
    protected function setShopwareDetailActive(Variant $icVariant, bool $active)
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
        $attribute->setDcIcRetailPrice($icVariant->getRetailPrice());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcInstock($this->client->getStock($icVariant));
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
            /** @var \Shopware\Models\Article\Article $article */
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
     * Set the retail price of Shopware detail associated to the given InnoCigs variant
     *
     * @param Variant $icVariant
     */

    protected function setRetailPrice(Variant $icVariant)
    {
        $swDetail = $icVariant->getDetail();
        if (! $swDetail) return;

        $price = $this->getPrice($swDetail);

        $tax = $swDetail->getArticle()->getTax()->getTax();
        $netPrice = $icVariant->getRetailPrice() / (1 + ($tax / 100));
        $price->setPrice($netPrice);
        $price->setFrom(1);
        $price->setTo(null);
    }

    /**
     * If supplied $article has a supplier then get it by name from Shopware or create it if necessary.
     * Otherwise do the same with default supplier name 'unknown'
     *
     * @param Article $article
     * @return Supplier
     */
    protected function getSupplier(Article $article)
    {
        $supplierName = $article->getSupplier() ?? 'unknown';
        $supplier = $this->modelManager->getRepository(Supplier::class)->findOneBy(['name' => $supplierName]);
        if (!$supplier) {
            $supplier = new Supplier();
            $this->modelManager->persist($supplier);
            $supplier->setName($supplierName);
        }
        return $supplier;
    }

    /**
     * Returns a Tax object for the given tax value. If the requested Tax object does not exist
     * it will be created.
     *
     * @param float $taxValue
     * @return object|Tax|null
     */
    protected function getTax(float $taxValue = 19.0)
    {
        $tax = $this->modelManager->getRepository(Tax::class)->findOneBy(['tax' => $taxValue]);
        if (! $tax instanceof Tax) {
            $name = sprintf('Tax (%.2f)', $taxValue);
            $tax = new Tax();
            $this->modelManager->persist($tax);
            $tax->setName($name);
            $tax->setTax($taxValue);
        }
        return $tax;
    }

    /**
     * Set the reference price for liquid articles.
     *
     * @param Article $icArticle
     */
    protected function setReferencePrice(Article $icArticle)
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
            if (! $swDetail) continue;
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
            $unit = $this->getUnit('ml');
            $swDetail->setUnit($unit);
        }
    }

    /**
     * Returns a Unit object for a given name. If the Unit Object is not available
     * it will be created.
     *
     * @param string $name
     * @return object|Unit|null
     */
    protected function getUnit(string $name)
    {
        $unit = $this->modelManager->getRepository(Unit::class)->findOneBy(['name' => $name]);
        if (!$unit instanceof Unit) {
            $unit = new Unit();
            $this->modelManager->persist($unit);
            $unit->setName($name);
        }
        return $unit;
    }

    /**
     * Creates and returns a price object for the customer group identified by $key
     * which is related to the given Shopware detail and the assiciated Shopware article.
     *
     * @param Detail $swDetail
     * @param string $key       customer group key
     * @return Price
     */
    protected function createPrice(Detail $swDetail, string $key)
    {
        $customerGroup = $this->customerGroups[$key];
        if ($customerGroup === null) {
            throw new \RuntimeException(__FUNCTION__ . ': Invalid customer group key ' . $key . '.');
        }
        $price = new Price();
        $this->modelManager->persist($price);
        $price->setCustomerGroup($customerGroup);
        $price->setArticle($swDetail->getArticle());
        $price->setDetail($swDetail);
        return $price;
    }

    /**
     * Returns the price object for the customer group with the given key of the given
     * Shopware detail object. If the price object is not found it will be created and
     * added to the Shopware detail object.
     *
     * @param Detail $swDetail
     * @param string $key           customer group key
     * @return Price
     */
    protected function getPrice(Detail $swDetail, string $key = 'EK')
    {
        $prices = $swDetail->getPrices();
        /** @var Price $price */
        foreach ($prices as $price) {
            if ($price->getCustomerGroup()->getKey() === $key) {
                return $price;
            }
        }
        $price = $this->createPrice($swDetail, $key);
        $swDetail->getPrices()->add($price);
        return $price;
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
     * Add all articles of the given Innocigs article collection to the target collection.
     * No duplicates.
     *
     * @param Collection $articles
     * @param Collection $collection
     */
    protected function addArticlesToCollection(Collection $articles, Collection $collection)
    {
        foreach ($articles as $article) {
            if (!$collection->contains($article)) {
                $collection->add($article);
            }
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
