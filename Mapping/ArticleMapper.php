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
        true => 1,
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


    public function __construct(
        ModelManager $modelManager,
        ArticleOptionMapper $optionMapper,
        MediaTool $mediaTool,
        ImportMapper $client,
        EntitiyValidator $validator,
        Config $config,
        LoggerInterface $log)
    {
        $this->modelManager = $modelManager;
        $this->optionMapper = $optionMapper;
        $this->mediaTool = $mediaTool;
        $this->client = $client;
        $this->validator = $validator;
        $this->config = $config;
        $this->log = $log;
        $this->articleTool = new ArticleTool();
        $this->dropshippersCompanionPresent = $this->validateDropshippersCompanion();
    }

    public function handleActiveStateChange(Article $icArticle)
    {
        $swArticle = $this->getShopwareArticle($icArticle);
        $active = $icArticle->isActive();
        if ($swArticle === null && $active) {
            $swArticle = $this->createShopwareArticle($icArticle);
        }
        if ($swArticle !== null) {
            $this->adjustShopwareArticleActiveState($icArticle);
        }
        return $active ? $swArticle : true;
    }

    public function createShopwareArticle(Article $icArticle) : ?ShopwareArticle
    {
        // do nothing if either the article or all of its variants are set to get not accepted
        //
        if (! $this->validator->validateArticle($icArticle)) {
            return null;
        }
        $this->mediaTool->init();
        $swArticle = new ShopwareArticle();
        $this->modelManager->persist($swArticle);
        $icArticle->setArticle($swArticle);

        $this->setShopwareArticleProperties($icArticle, $swArticle, true);
        $this->createShopwareDetails($icArticle, $swArticle);

        $swArticle->setActive(false);
        $this->modelManager->flush();
        return $swArticle;
    }

    public function updateShopwareArticle(Article $icArticle, bool $force = false)
    {
        $swArticle = $this->getShopwareArticle($icArticle);
        if (! $swArticle) return;

        // deactivate article if it is not accepted any longer
        if (! $this->validator->validateArticle($icArticle)) {
            $icArticle->setActive(false);
            $this->handleActiveStateChange($icArticle);
            return;
        }
        $this->setShopwareArticleProperties($icArticle, $swArticle, $force);
        $this->updateShopwareDetails($icArticle, $swArticle);

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
        if ($force || ! $probe || $probe === '') {
            $swArticle->setDescriptionLong($icArticle->getDescription());
        }

        $probe = $swArticle->getDescription();
        if ($force || ! $probe || $probe === '') {
            $swArticle->setDescription('');
        }

        $probe = $swArticle->getKeywords();
        if ($force || ! $probe || $probe === '') {
            $swArticle->setKeywords('');
        }

        $probe = $swArticle->getMetaTitle();
        if ($force || ! $probe || $probe === '') {
            $metaTitle = 'Vapee.de: ' . preg_replace('~\(\d+ Stück pro Packung\)~','',$icArticle->getName());
            $swArticle->setMetaTitle($metaTitle);
        }

        $probe = $swArticle->getName();
        if ($force || ! $probe || $probe === '') {
            $swArticle->setName($icArticle->getName());
        }

        $swArticle->setTax($this->getTax($icArticle->getTax()));
        $swArticle->setSupplier($this->getSupplier($icArticle));

        $this->setRelatedArticles($icArticle, $swArticle);
        $this->setSimilarArticles($icArticle, $swArticle);

        $this->setCategories($icArticle, $swArticle);
    }
    // Set Shopware article's active state according to Innocigs article active state

    public function adjustShopwareArticleActiveState(Article $icArticle)
    {
        $swArticle = $this->getShopwareArticle($icArticle);
        if ($swArticle === null) return null;

        $state = $icArticle->isActive();
        $swArticle->setActive($state);

        $variants = $icArticle->getVariants();
        foreach ($variants as $variant) {
            $swDetail = $this->getShopwareDetail($variant);
            if ($swDetail === null) continue;
            $swDetail->setActive($state);
            $this->setDropship($swDetail, $variant, $state);
        }
    }

    protected function getShopwareArticles(Collection $icArticles) : ArrayCollection
    {
        $swArticles = [];
        foreach ($icArticles as $icArticle)
        {
            $swArticle = $this->getShopwareArticle($icArticle);
            if ($swArticle !== null) {
                $swArticles[] = $swArticle;
            }
        }
        return new ArrayCollection($swArticles);
    }

    protected function activateShopwareArticles(Collection $icArticles) : ArrayCollection
    {
        $swArticles = [];
        foreach ($icArticles as $icArticle)
        {
            $icArticle->setActive(true);
            $swArticle = $this->handleActiveStateChange($icArticle);
            $icArticle->setActive($swArticle !== null);
            if ($icArticle->isActive()) {
                $swArticles[] = $swArticle;
            }
        }
        return new ArrayCollection($swArticles);
    }

    protected function addArticlesToCollection(Collection $articles, Collection $collection)
    {
        foreach ($articles as $article) {
            if (! $collection->contains($article)) {
                $collection->add($article);
            }
        }
    }

    protected function setAssociatedArticles(bool $activate, Collection $related, Collection $target)
    {
        $swRelated = $activate ? $this->activateShopwareArticles($related) : $this->getShopwareArticles($related);
        $this->addArticlesToCollection($swRelated, $target);
    }

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

    protected function removeShopwareDetails(ShopwareArticle $swArticle)
    {
        $details = $swArticle->getDetails();
        foreach ($details as $detail) {
            $this->modelManager->remove($detail);
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
            if (! $variant->isAccepted()) continue;

            $swDetail = $this->createShopwareDetail($variant, $swArticle);
            if ($isMainDetail) {
                $swDetail->setKind(1);
                $swArticle->setMainDetail($swDetail);
                $swArticle->setAttribute($swDetail->getAttribute());
                $isMainDetail = false;
            }
        }
    }

    protected function createShopwareDetail(Variant $variant, ShopwareArticle $swArticle){
        $detail = new Detail();
        $this->modelManager->persist($detail);
        $variant->setDetail($detail);
        $detail->setArticle($swArticle);

        // The class \Shopware\Models\Attribute\Article ist part of the Shopware attribute system.
        // It gets (re)generated automatically by Shopware core, when attributes are added/removed
        // via the attribute crud service. It is located in \var\cache\production\doctrine\attributes.
        $attribute = new \Shopware\Models\Attribute\Article();
        $detail->setAttribute($attribute);

        $this->setShopwareDetailProperties($variant, $detail);
        $detail->setActive(false);

        $prices = $this->createPrice($variant, $swArticle, $detail);
        $detail->setPrices($prices);
        // Note: shopware options are added non persistently to variants when configurator set is created
        $detail->setConfiguratorOptions(new ArrayCollection($this->optionMapper->getShopwareOptions($variant)));

        $this->mediaTool->setArticleImages($variant->getImages(), $swArticle, $detail);

        return $detail;
    }

    protected function setShopwareDetailProperties(Variant $variant, Detail $detail)
    {
        $icArticle = $variant->getArticle();

        $detail->setNumber($variant->getNumber());
        $detail->setEan($variant->getEan());
        $detail->setStockMin(0);
        $detail->setSupplierNumber('');
        $detail->setAdditionalText('');
        $detail->setPackUnit('');
        $detail->setShippingTime(5);
        $detail->setPurchasePrice($variant->getPurchasePrice());
        $detail->setLastStock(0);
        $this->setReferencePrice($variant->getArticle(), $detail);
        $detail->setKind(2);

        $attribute = $detail->getAttribute();
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiBrand($icArticle->getBrand());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiSupplier($icArticle->getSupplier());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiFlavor($icArticle->getFlavor());
    }

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

    protected function setDropship(Detail $swDetail, Variant $variant, bool $state)
    {
        $attribute = $swDetail->getAttribute();
        if (! $this->dropshippersCompanionPresent) {
            $this->log->warn(sprintf('%s: Could not prepare Shopware article "%s" for dropship orders. Dropshippers Companion is not installed.',
                __FUNCTION__,
                $variant->getNumber()
            ));
            return;
        }
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

    protected function createPrice(Variant $variant, ShopwareArticle $swArticle, Detail $detail){
        $tax = $swArticle->getTax()->getTax();
        $netPrice = $variant->getRetailPrice() / (1 + ($tax/100));
        $price = new Price();
        $this->modelManager->persist($price);
        $price->setPrice($netPrice);
        $price->setFrom(1);
        $price->setTo(null);
        $customerGroup = $this->modelManager->getRepository(Group::class)->findOneBy(['key' => 'EK']);
        $price->setCustomerGroup($customerGroup);
        $price->setArticle($swArticle);
        $price->setDetail($detail);

        return new ArrayCollection([$price]);
    }

    /**
     * If supplied $article has a supplier then get it by name from Shopware or create it if necessary.
     * Otherwise do the same with default supplier name 'unknown'
     *
     * @param Article $article
     * @return Supplier
     */
    protected function getSupplier(Article $article) {
        $supplierName = $article->getSupplier() ?? 'unknown';
        $supplier = $this->modelManager->getRepository(Supplier::class)->findOneBy(['name' => $supplierName]);
        if (! $supplier) {
            $supplier = new Supplier();
            $this->modelManager->persist($supplier);
            $supplier->setName($supplierName);
        }
        return $supplier;
    }

    protected function getTax(float $taxValue = 19.0) {
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

    protected function getUnit(string $name)
    {
        $unit = $this->modelManager->getRepository(Unit::class)->findOneBy(['name' => $name]);
        if (! $unit instanceof Unit) {
            $unit = new Unit();
            $this->modelManager->persist($unit);
            $unit->setName($name);
        }
        return $unit;
    }

    protected function setReferencePrice(Article $article, Detail $detail)
    {
        // These products may need a reference price, unit is ml
        if (preg_match('~(Liquid)|(Aromen)|(Basen)|(Shake \& Vape)~', $article->getCategory()) !== 1) return;
        // Do not add reference price on multi item packs
        $name = $article->getName();
        if (preg_match('~\(\d+ Stück pro Packung\)~', $name) === 1) return;

        $matches = [];
        preg_match('~(\d+(\.\d+)?) ml~', $name, $matches);
        // If there's there are no ml in the product name we exit
        if (empty($matches)) return;
        // remove thousands punctuation
        $volume = $matches[1];
        $volume = str_replace('.', '', $volume);

        // calculate the reference volume
        $reference = $volume < 100 ? 100 : ($volume < 1000 ? 1000 : 0);
        // Exit if we have no reference volume
        if ($reference === 0) return;

        // set reference volume and unit
        $detail->setPurchaseUnit($volume);
        $detail->setReferenceUnit($reference);
        $unit = $this->getUnit('ml');
        $detail->setUnit($unit);
    }

    protected function getShopwareDetail(Variant $variant) : ?Detail
    {
        $swDetail = $variant->getDetail();
        if ($swDetail === null) {
            $swDetail = $this->modelManager->getRepository(Variant::class)->getShopwareDetail($variant);
            $variant->setDetail($swDetail);
        }
        return $swDetail;
    }

    protected function getShopwareArticle(Article $icArticle) : ?ShopwareArticle
    {
        $swArticle = $icArticle->getArticle();
        if ($swArticle === null) {
            $swArticle = $this->modelManager->getRepository(Article::class)->getShopwareArticle($icArticle);
            $icArticle->setArticle($swArticle);
        }
        return $swArticle;
    }
}