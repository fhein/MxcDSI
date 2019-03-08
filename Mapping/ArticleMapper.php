<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
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
    protected $kind = [
        true  => 1,
        false => 2,
    ];

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
    /**
     * @var EntitiyValidator $validator
     */
    protected $validator;

    /** @var bool */
    protected $dropshippersCompanionPresent;

    protected $articleTool;

    protected $shopwareGroups = [];
    protected $shopwareGroupRepository = null;
    protected $shopwareGroupLookup = [];

    protected $ekCustomerGroup;

    public function __construct(
        ModelManager $modelManager,
        ArticleOptionMapper $optionMapper,
        MediaTool $mediaTool,
        ImportMapper $client,
        EntitiyValidator $validator,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->optionMapper = $optionMapper;
        $this->mediaTool = $mediaTool;
        $this->client = $client;
        $this->validator = $validator;
        $this->config = $config;
        $this->log = $log;
        $this->articleTool = new ArticleTool();
        $this->dropshippersCompanionPresent = $this->validateDropshippersCompanion();
        if (! $this->dropshippersCompanionPresent) {
            $this->log->warn('Can not prepare articles for dropship orders. Dropshipper\'s Companion is not installed.');
        }
        $this->ekCustomerGroup = $this->modelManager->getRepository(Group::class)->findOneBy(['key' => 'EK']);
    }

    public function handleActiveStateChange(Article $icArticle)
    {
        $swArticle = $icArticle->getArticle();
        $active = $icArticle->isActive();
        if ($swArticle === null && $active) {
            $swArticle = $this->createShopwareArticle($icArticle);
        }
        if ($swArticle !== null) {
            $this->adjustShopwareArticleActiveState($icArticle);
        }
        return $active ? $swArticle : true;
    }

    public function createShopwareArticle(Article $icArticle): ?ShopwareArticle
    {
        // do nothing if either the article or all of its variants are set to get not accepted
        //
        if (!$this->validator->validateArticle($icArticle)) {
            return null;
        }
        $swArticle = new ShopwareArticle();
        $this->modelManager->persist($swArticle);
        $icArticle->setArticle($swArticle);

        $this->setShopwareArticleProperties($icArticle, $swArticle, true);
        $this->createShopwareDetails($icArticle, $swArticle);
        $this->mediaTool->setArticleImages($icArticle, $swArticle);
        $this->setReferencePrice($icArticle);

        $swArticle->setActive(false);
        $this->modelManager->flush();
        return $swArticle;
    }

    public function updateShopwareArticle(Article $icArticle, bool $force = false)
    {
        $swArticle = $icArticle->getArticle();
        if (!$swArticle) {
            return;
        }

        // deactivate article if it is not accepted any longer
        if (!$this->validator->validateArticle($icArticle)) {
            $icArticle->setActive(false);
            $this->handleActiveStateChange($icArticle);
            return;
        }

        $this->setShopwareArticleProperties($icArticle, $swArticle, $force);
        $this->updateShopwareDetails($icArticle, $swArticle);

        $this->mediaTool->setArticleImages($icArticle, $swArticle);
        $this->setReferencePrice($icArticle);

        $this->modelManager->flush();
    }

    protected function updateShopwareDetails(Article $icArticle, ShopwareArticle $swArticle)
    {
        $swDetails = $swArticle->getDetails();
        $variants = $icArticle->getVariants();
        $deletedVariants = [];

        /** @var Detail $swDetail */
        foreach ($swDetails as $swDetail) {
            $deletedVariants[$swDetail->getNumber()] = $swDetail;
        }

        /** @var Variant $variant */
        $newVariants = [];
        foreach ($variants as $variant) {
            $number = $variant->getNumber();
            if ($deletedVariants[$number]) {
                unset($deletedVariants[$number]);
            } else {
                $newVariants[] = $variant;
            }
        }
        // @todo: Add new Variants, delete/deactivate obsolete variants

    }

    protected function setShopwareArticleProperties(Article $icArticle, ShopwareArticle $swArticle, bool $force)
    {
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

        $this->setRelatedArticles($icArticle, $swArticle);
        $this->setSimilarArticles($icArticle, $swArticle);

        $this->setCategories($icArticle, $swArticle);
    }

    // Set Shopware article's active state according to Innocigs article active state

    protected function setRelatedArticles(Article $icArticle, ShopwareArticle $swArticle)
    {
        $this->setAssociatedArticles(
            $icArticle->getActivateRelatedArticles(),
            $icArticle->getRelatedArticles(),
            $swArticle->getRelated()
        );
    }

    public function setSimilarArticles(Article $icArticle, ShopwareArticle $swArticle)
    {
        $this->setAssociatedArticles(
            $icArticle->getActivateSimilarArticles(),
            $icArticle->getSimilarArticles(),
            $swArticle->getSimilar());
    }

    public function adjustShopwareArticleActiveState(Article $icArticle)
    {
        $swArticle = $icArticle->getArticle();
        if ($swArticle === null) {
            return null;
        }

        $state = $icArticle->isActive();
        $swArticle->setActive($state);

        $variants = $icArticle->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $swDetail = $variant->getDetail();
            if ($swDetail === null) continue;
            $swDetail->setActive($state);
            $this->setDropship($swDetail, $variant, $state);
        }
    }

    protected function removeShopwareDetails(ShopwareArticle $swArticle)
    {
        $details = $swArticle->getDetails();
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $attribute = $detail->getAttribute();
            $this->modelManager->remove($detail);
            $this->modelManager->remove($attribute);
        }
        $details->clear();
    }

    /**
     * @param Article $icArticle
     * @param ShopwareArticle $swArticle
     */
    protected function createShopwareDetails(Article $icArticle, ShopwareArticle $swArticle): void
    {
        $set = $this->optionMapper->createConfiguratorSet($icArticle);
        $swArticle->setConfiguratorSet($set);

        $variants = $icArticle->getVariants();

        $isMainDetail = true;
        foreach ($variants as $variant) {
            if (! $this->validator->validateVariant($variant)) {
                continue;
            }

            $swDetail = $this->createShopwareDetail($variant, $swArticle);
            $swDetail->setKind(2);
            if ($isMainDetail) {
                $swDetail->setKind(1);
                $swArticle->setMainDetail($swDetail);
                $swArticle->setAttribute($swDetail->getAttribute());
                $isMainDetail = false;
            }
        }
    }

    protected function createShopwareDetail(Variant $variant, ShopwareArticle $swArticle)
    {
        $detail = new Detail();
        $this->modelManager->persist($detail);
        // The next two settings have to be made upfront because the later code relies on these
        $variant->setDetail($detail);
        $detail->setArticle($swArticle);

        // The class \Shopware\Models\Attribute\Article ist part of the Shopware attribute system.
        // It gets (re)generated automatically by Shopware core, when attributes are added/removed
        // via the attribute crud service. It is located in \var\cache\production\doctrine\attributes.
        $attribute = new \Shopware\Models\Attribute\Article();
        $detail->setAttribute($attribute);

        $this->setShopwareDetailProperties($variant, $detail, true);
        $detail->setActive(false);

        // Note: shopware options were added non persistently to variants when configurator set was created
        $detail->setConfiguratorOptions(new ArrayCollection($variant->getShopwareOptions()));

        return $detail;
    }

    protected function setShopwareDetailProperties(Variant $icVariant, Detail $swDetail, bool $force)
    {
        $icArticle = $icVariant->getArticle();

        $swDetail->setNumber($icVariant->getNumber());
        $swDetail->setEan($icVariant->getEan());
        $swDetail->setPurchasePrice($icVariant->getPurchasePrice());

        $this->setRetailPrice($icVariant);

        $attribute = $swDetail->getAttribute();
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiBrand($icArticle->getBrand());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiSupplier($icArticle->getSupplier());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiFlavor($icArticle->getFlavor());

        $probe = $swDetail->getShippingTime();
        if ($force || ! $probe) {
            $swDetail->setShippingTime(5);
        }

        $probe = $swDetail->getLastStock();
        if ($force || ! $probe) {
            // @todo
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

    protected function setDropship(Detail $swDetail, Variant $variant, bool $state)
    {
        if (!$this->dropshippersCompanionPresent) return;

        $attribute = $swDetail->getAttribute();
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcActive($state);
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcOrderNumber($variant->getIcNumber());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcArticleName($variant->getArticle()->getName());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcPurchasingPrice($variant->getPurchasePrice());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcRetailPrice($variant->getRetailPrice());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcInstock($this->client->getStock($variant));
    }

    protected function createCategory(Category $parent, string $name)
    {
        $child = new Category();
        $this->modelManager->persist($child);
        $child->setName($name);
        $child->setParent($parent);
        $child->setChanged();
        if ($parent->getChildren()->count() === 0 && $parent->getArticles()->count() > 0) {
            /** @var \Shopware\Models\Article\Article $article */
            foreach ($parent->getArticles() as $article) {
                $article->removeCategory($parent);
                $article->addCategory($child);
            }
            $parent->setChanged();
        }
        return $child;
    }

    protected function getCategory(string $path, Category $root = null)
    {
        $repository = $this->modelManager->getRepository(Category::class);
        /** @var Category $parent */
        $parent = ($root !== null) ? $root : $repository->findOneBy(['parentId' => null]);
        $path = explode(' > ', $path);
        foreach ($path as $categoryName) {
            $child = $repository->findOneBy(['name' => $categoryName, 'parentId' => $parent->getId()]);
            $parent = ($child !== null) ? $child : $this->createCategory($parent, $categoryName);
        }
        return $parent;
    }

    protected function setCategories(Article $article, ShopwareArticle $swArticle)
    {
        $swArticle->getCategories()->clear();
        $root = $this->config->get('root_category', 'Deutsch');
        $root = $this->getCategory($root);
        $catgories = explode(MXC_DELIMITER_L1, $article->getCategory());
        foreach ($catgories as $category) {
            $swArticle->addCategory($this->getCategory($category, $root));
        }
    }

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
        $volume = $matches[1];
        $volume = str_replace('.', '', $volume);

        // calculate the reference volume
        $reference = $volume < 100 ? 100 : ($volume < 1000 ? 1000 : 0);
        // Exit if we have no reference volume
        if ($reference === 0) {
            return;
        }

        $icVariants = $icArticle->getVariants();
        /** @var Variant $icVariant */
        foreach ($icVariants as $icVariant) {
            $swDetail = $icVariant->getDetail();
            if (! $swDetail) continue;
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
     * Creates and returns a price object for the 'EK' customer group which is
     * related to the given Shopware detail and the assiciated Shopware article.
     *
     * @param Detail $swDetail
     * @return Price
     */
    protected function createPrice(Detail $swDetail)
    {
        $price = new Price();
        $this->modelManager->persist($price);
        $price->setCustomerGroup($this->ekCustomerGroup);
        $price->setArticle($swDetail->getArticle());
        $price->setDetail($swDetail);
        return $price;
    }

    /**
     * Returns the price object for the customer group 'EK' of the given Shopware
     * detail object. If the price object is not found it will be created and added
     * to the Shopware detail object.
     *
     * @param Detail $swDetail
     * @return Price
     */
    protected function getPrice(Detail $swDetail)
    {
        $prices = $swDetail->getPrices();
        /** @var Price $price */
        foreach ($prices as $price) {
            if ($price->getCustomerGroup()->getKey() === 'EK') {
                return $price;
            }
        }
        $price = $this->createPrice($swDetail);
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
     * For a given collection of InnoCigs articles return a collection of Shopware $articles.
     * If an InnoCigs article has no Shopware article associated, the Shopware article will
     * be created.
     *
     * @param Collection $icArticles
     * @return ArrayCollection
     */
    protected function activateShopwareArticles(Collection $icArticles): ArrayCollection
    {
        $swArticles = [];
        foreach ($icArticles as $icArticle) {
            $icArticle->setActive(true);
            $swArticle = $this->handleActiveStateChange($icArticle);
            $icArticle->setActive($swArticle !== null);
            if ($icArticle->isActive()) {
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
     * Add the Shopware articles which are related to a given list of InnoCigs articles to a given
     * target collection. Used to set similar articles and related articles of Shopware articles.
     *
     * @param bool $activate            If true then missing Shopware articles get created
     * @param Collection $icArticles    list of InnoCigs articles
     * @param Collection $target        target collection
     */
    protected function setAssociatedArticles(bool $activate, Collection $icArticles, Collection $target)
    {
        $swRelated = $activate ? $this->activateShopwareArticles($icArticles) : $this->getShopwareArticles($icArticles);
        $this->addArticlesToCollection($swRelated, $target);
    }

    /**
     * Check if the Dropshipper's Companion for InnoCigs Shopware plugin is installed or not.
     * If installed, check if the required API's provided by the companion plugin are present.
     *
     * @return bool
     */
    protected function validateDropshippersCompanion(): bool
    {
        // This line validates the presence of the InnocigsPlugin;
        if (null === $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs'])) {
            return false;
        };

        // These do not actually validate the presence of the Innocigs plugin.
        // We validate the presence of the attributes the Innocigs plugin uses instead.
        // If they apply changes to the attributes they use, we can not proceed.
        $className = 'Shopware\Models\Attribute\Article';
        return method_exists($className, 'setDcIcOrderNumber')
            && method_exists($className, 'setDcIcArticleName')
            && method_exists($className, 'setDcIcPurchasingPrice')
            && method_exists($className, 'setDcIcRetailPrice')
            && method_exists($className, 'setDcIcActive')
            && method_exists($className, 'setDcIcInstock');
    }
}
