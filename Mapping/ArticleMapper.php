<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
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
    }

    public function createShopwareArticle(Article $icArticle) : ?ShopwareArticle
    {
        // do nothing if either the article or all of its variants are set to get not accepted
        //
        if (! $this->validator->validateArticle($icArticle)) {
            return null;
        }

        $swArticle = $this->getShopwareArticle($icArticle) ?? new ShopwareArticle();
        $this->modelManager->persist($swArticle);

        $icArticle->setArticle($swArticle);

        $swArticle->setName($icArticle->getName());
        $swArticle->setTax($this->getTax());
        $swArticle->setSupplier($this->getSupplier($icArticle));
        $swArticle->setDescriptionLong($icArticle->getDescription());

        // @todo: Set Shortdescription (SEO)
        $swArticle->setDescription('');
        // @todo: Set Keywords (SEO)
        $swArticle->setKeywords('');

        $metaTitle = 'Vapee.de: ' . preg_replace('~\(\d+ Stück pro Packung\)~','',$icArticle->getName());
        $swArticle->setMetaTitle($metaTitle);

        $swArticle->setActive(true);

        $set = $this->optionMapper->createConfiguratorSet($icArticle);
        $swArticle->setConfiguratorSet($set);
        $this->addCategories($icArticle, $swArticle);

        //create details from innocigs variants
        $variants = $icArticle->getVariants();

        $isMainDetail = true;
        foreach($variants as $variant){
            if (! $variant->isAccepted()) continue;
            $swDetail = $this->getShopwareDetail($variant) ?? $this->createShopwareDetail($variant, $swArticle, $isMainDetail);

            if($isMainDetail){
                $swArticle->setMainDetail($swDetail);
                $isMainDetail = false;
            }
        }

        $this->setRelatedArticles($icArticle, $swArticle);
        $this->setSimilarArticles($icArticle, $swArticle);

        $this->modelManager->flush();
        return $swArticle;
    }

    /**
     * Variants hold a reference to the associated shopware Detail.
     * Shopware Detail records may have been removed by other modules.
     *
     * @param Variant $variant
     * @return Detail|null
     */
    protected function getShopwareDetail(Variant $variant) {
        $swDetail = $variant->getDetail();
        if ($swDetail === null) {
            $swDetail = $this->modelManager->getRepository(Variant::class)->getShopwareDetail($variant);
            $variant->setDetail($swDetail);
        }
        return $swDetail;
    }

    protected function getShopwareArticle(Article $icArticle)
    {
        $swArticle = $icArticle->getArticle();
        if ($swArticle === null) {
            $swArticle = $this->modelManager->getRepository(Article::class)->getShopwareArticle($icArticle);
            $icArticle->setArticle($swArticle);
        }
        return $swArticle;
    }

    protected function setRelatedArticles(Article $icArticle, ShopwareArticle $swArticle)
    {
        $icRelatedArticles = $icArticle->getRelatedArticles();
        $swRelatedArticles = [];
        $createRelatedArticles = $icArticle->getActivateRelatedArticles();
        /** @var Article $icRelatedArticle */
        foreach ($icRelatedArticles as $icRelatedArticle)
        {
            $swRelatedArticle = $this->getShopwareArticle($icRelatedArticle);
            if ((null === $swRelatedArticle) && $createRelatedArticles) {
                $this->mediaTool->init();
                $swRelatedArticle = $this->createShopwareArticle($icRelatedArticle);
                $icRelatedArticle->setActive(true);
            }
            if (null !== $swRelatedArticle) {
                $swRelatedArticles[] = $swRelatedArticle;
            }
        }
        if (! empty($swRelatedArticles)) {
            $swArticle->setRelated(new ArrayCollection($swRelatedArticles));
        }
    }
    
    public function setSimilarArticles(Article $icArticle, ShopwareArticle $swArticle)
    {
        $icSimilarArticles = $icArticle->getSimilarArticles();
        $swSimilarArticles = [];
        $createSimilarArticles = $icArticle->getActivateSimilarArticles();
        /** @var Article $icSimilarArticle */
        foreach ($icSimilarArticles as $icSimilarArticle)
        {
            $swSimilarArticle = $this->getShopwareArticle($icSimilarArticle);
            if ((null === $swSimilarArticle) && $createSimilarArticles) {
                $this->mediaTool->init();
                $swSimilarArticle = $this->createShopwareArticle($icSimilarArticle);
                $icSimilarArticle->setActive(true);
            }
            if (null !== $swSimilarArticle) {
                $swSimilarArticles[] = $swSimilarArticle;
            }
        }
        if (! empty($swSimilarArticles)) {
            $swArticle->setSimilar(new ArrayCollection($swSimilarArticles));
        }
    }

    protected function createShopwareDetail(Variant $variant, ShopwareArticle $swArticle, bool $isMainDetail){
        $detail = new Detail();
        $this->modelManager->persist($detail);
        $variant->setDetail($detail);

        // The class \Shopware\Models\Attribute\Article ist part of the Shopware attribute system.
        // It gets (re)generated automatically by Shopware core, when attributes are added/removed
        // via the attribute crud service. It is located in \var\cache\production\doctrine\attributes.
        $attribute = new \Shopware\Models\Attribute\Article();
        $detail->setAttribute($attribute);
        if ($isMainDetail) {
            $swArticle->setAttribute($attribute);
        }
        $icArticle = $variant->getArticle();

        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiBrand($icArticle->getBrand());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiSupplier($icArticle->getSupplier());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setMxcDsiFlavor($icArticle->getFlavor());


        $detail->setNumber($variant->getNumber());
        $detail->setEan($variant->getEan());
        $detail->setStockMin(0);
        $detail->setSupplierNumber('');
        $detail->setAdditionalText('');
        $detail->setPackUnit('');
        $detail->setShippingTime(5);
        $detail->setPurchasePrice($variant->getPurchasePrice());

        $isMainDetail ? $detail->setKind(1) : $detail->setKind(2);

        $detail->setActive(true);
        $detail->setLastStock(0);

        $detail->setArticle($swArticle);

        $this->setReferencePrice($variant->getArticle(), $detail);

        $prices = $this->createPrice($variant, $swArticle, $detail);
        $detail->setPrices($prices);
        // Note: shopware options are added non persistently to variants when
        // configurator set is created
        $detail->setConfiguratorOptions(new ArrayCollection($this->optionMapper->getShopwareOptions($variant)));


        $this->mediaTool->setArticleImages($variant->getImages(), $swArticle, $detail);

        $this->enableDropship($variant, $attribute);

        return $detail;
    }

    protected function validateDropshipPlugin(\Shopware\Models\Attribute\Article $attribute): bool
    {
        // This line validates the presence of the InnocigsPlugin;
        if (null === $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs'])) {
            return false;
        };

        // These do not actually validate the presence of the Innocigs plugin.
        // We validate the presence of the attributes the Innocigs plugin uses instead.
        // If they apply changes to the attributes they use, we can not proceed.
        return method_exists($attribute, 'setDcIcOrderNumber')
            && method_exists($attribute, 'setDcIcArticleName')
            && method_exists($attribute, 'setDcIcPurchasingPrice')
            && method_exists($attribute, 'setDcIcRetailPrice')
            && method_exists($attribute, 'setDcIcActive')
            && method_exists($attribute, 'setDcIcInstock');
    }

    protected function enableDropship(Variant $variant, \Shopware\Models\Attribute\Article $attribute)
    {
        if (! $this->validateDropshipPlugin($attribute)) {
            $this->log->warn(sprintf('%s: Could not prepare Shopware article "%s" for dropship orders. Dropshippers Companion is not installed.',
                __FUNCTION__,
                $variant->getNumber()
            ));
            return;
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcActive(true);
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

    protected function addCategories(Article $article, ShopwareArticle $swArticle)
    {
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

    protected function deactivateShopwareArticle(Article $icArticle) {
        $this->log->info('Remove Shopware article for ' . $icArticle->getName());

        // This code is meant to delete an article, but it does not work yet
        // See ArticleTool

//        $swArticle = $this->getShopwareArticle($icArticle);
//        if ($swArticle === null) return true;
//
//        $this->articleTool->deleteArticle($swArticle);
//
//        if ($icArticle->getActivateRelatedArticles()) {
//            $icRelatedArticles = $icArticle->getRelatedArticles();
//            foreach ($icRelatedArticles as $icRelatedArticle) {
//                $this->deactivateShopwareArticle($icRelatedArticle);
//            }
//        }
        return true;
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

    public function handleActiveStateChange(Article $article)
    {
        $result = $article->isActive() ?
            $this->createShopwareArticle($article) :
            $this->deactivateShopwareArticle($article);
        return $result;
    }
}