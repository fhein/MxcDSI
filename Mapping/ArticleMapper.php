<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Import\PropertyMapper;
use MxcDropshipInnocigs\Models\Current\Article;
use MxcDropshipInnocigs\Models\Current\Variant;
use MxcDropshipInnocigs\Toolbox\Media\MediaTool;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Customer\Group;
use Shopware\Models\Plugin\Plugin;
use Shopware\Models\Tax\Tax;

class ArticleMapper
{
    /**
     * @var LoggerInterface $log
     */
    protected $log;

    /**
     * @var ArticleOptionMapper $optionMapper
     */
    protected $optionMapper;

    /**
     * @var PropertyMapper $propertyMapper
     */
    protected $propertyMapper;

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
     * @var InnocigsEntityValidator $validator
     */
    protected $validator;

    protected $shopwareGroups = [];
    protected $shopwareGroupRepository = null;
    protected $shopwareGroupLookup = [];

    public function __construct(
        ModelManager $modelManager,
        ArticleOptionMapper $optionMapper,
        PropertyMapper $propertyMapper,
        MediaTool $mediaTool,
        ImportMapper $client,
        InnocigsEntityValidator $validator,
        LoggerInterface $log)
    {
        $this->modelManager = $modelManager;
        $this->optionMapper = $optionMapper;
        $this->propertyMapper = $propertyMapper;
        $this->mediaTool = $mediaTool;
        $this->client = $client;
        $this->validator = $validator;
        $this->log = $log;
    }

    public function createShopwareArticle(Article $article) {
        $this->log->enter();
        // do nothing if either the article or all of its variants are set to get not accepted
        if (! $this->validator->validateArticle($article)) {
            return false;
        }

        $swArticle = $this->getShopwareArticle($article) ?? new ShopwareArticle();
        $this->modelManager->persist($swArticle);

        $name = $this->propertyMapper->mapArticleName($article->getName());

        // this will get the product detail record from InnoCigs which can hold a description
        $this->client->addArticleDetail($article);

        $article->setArticle($swArticle);

        $tax = $this->getTax();
        $supplier = $this->getSupplier($article);
        $swArticle->setName($name);
        $swArticle->setTax($tax);
        $swArticle->setSupplier($supplier);
        $swArticle->setMetaTitle('');
        $swArticle->setKeywords('');
        $swArticle->setDescription($article->getDescription());
        $swArticle->setDescriptionLong($article->getDescription());

        $swArticle->setActive(true);

        $set = $this->optionMapper->createConfiguratorSet($article);
        $swArticle->setConfiguratorSet($set);

        $this->mediaTool->setArticleImages($article->getImageUrl(), $swArticle);

        //create details from innocigs variants
        $variants = $article->getVariants();

        $isMainDetail = true;
        foreach($variants as $variant){
            if (! $variant->isAccepted()) continue;
            /**
             * @var Detail $swDetail
             */
            $code = $this->propertyMapper->mapVariantCode($variant->getCode());
            $swDetail = $this->modelManager->getRepository(Detail::class)->findOneBy([ 'number' => $code ])
                ?? $this->createShopwareDetail($variant, $swArticle, $isMainDetail);

            $this->modelManager->persist($swDetail);

            if($isMainDetail){
                $swArticle->setMainDetail($swDetail);
                $isMainDetail = false;
            }
        }
        $this->modelManager->flush();
        $this->log->leave();
        return true;
    }

    /**
     * Gets the Shopware ImportArticle by looking for the Shopware detail of the first variant for the supplied $article.
     * If it exists, we assume that the article and all other variants exist as well
     *
     * @param Article $article
     * @return null|ShopwareArticle
     */
    protected function getShopwareArticle(Article $article){
        $swArticle = null;
        $variants = $article->getVariants();
        $codes = [];
        foreach ($variants as $variant) {
            $codes[] = $this->propertyMapper->mapVariantCode($variant->getCode());
        }
        $expr = Criteria::expr();
        /**
         * @var Criteria $criteria
         */
        $criteria = Criteria::create()->where($expr->in('number', $codes));
        $swDetails = $this->modelManager->getRepository(Detail::class)->matching($criteria);

        if (! $swDetails->isEmpty()){
            $swArticle = $swDetails->offsetGet(0)->getArticle();
        }
        return $swArticle;
    }

    protected function createShopwareDetail(Variant $variant, ShopwareArticle $swArticle, bool $isMainDetail){
        $this->log->info(sprintf('%s: Creating detail record for InnoCigs variant %s',
            __FUNCTION__,
            $variant->getCode()
        ));

        $detail = new Detail();

        // The class \Shopware\Models\Attribute\ImportArticle ist part of the Shopware attribute system.
        // It gets (re)generated automatically by Shopware core, when attributes are added/removed
        // via the attribute crud service. It is located in \var\cache\production\doctrine\attributes.
        //
        if (class_exists('\Shopware\Models\Attribute\Article')) {
            $attribute = new \Shopware\Models\Attribute\Article();
            $detail->setAttribute($attribute);
            if ($isMainDetail) {
                $swArticle->setAttribute($attribute);
            }
            $icArticle = $variant->getArticle();
            /** @noinspection PhpUndefinedMethodInspection */
            $attribute->setMxcDsiBrand($icArticle->getBrand());
        } else {
            throw new Exception(__FUNCTION__ . ': Shopware article attribute model does not exist.');
        }

        $detail->setNumber($this->propertyMapper->mapVariantCode($variant->getCode()));
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
        // Todo: $detail->setPurchaseUnit();
        // Todo: $detail->setReferenceUnit();

        $detail->setArticle($swArticle);

        $prices = $this->createPrice($variant, $swArticle, $detail);
        $detail->setPrices($prices);
        // Note: shopware options are added non persistently to variants when
        // configurator set is created
        $detail->setConfiguratorOptions(new ArrayCollection($this->optionMapper->getShopwareOptions($variant)));
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
            && method_exists($attribute, 'setDcPurchasingPrice')
            && method_exists($attribute, 'setDcRetailPrice')
            && method_exists($attribute, 'setDcIcActive')
            && method_exists($attribute, 'setDcIcInstock');
    }

    protected function enableDropship(Variant $variant, \Shopware\Models\Attribute\Article $attribute)
    {
        if (! $this->validateDropshipPlugin($attribute)) {
            $this->log->warn(sprintf('%s: Could not prepare Shopware article "%s" for dropship orders. Dropshippers Companion is not installed.',
                __FUNCTION__,
                $variant->getCode()
            ));
            return;
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcActive(true);
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcOrderNumber($variant->getCode());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcArticleName($variant->getArticle()->getName());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcPurchasingPrice($variant->getPriceNet());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcRetailPrice($variant->getPriceRecommended());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcInstock($this->client->getStock($variant));
    }

    protected function createPrice(Variant $variant, ShopwareArticle $swArticle, Detail $detail){
        $tax = $this->getTax()->getTax();
        $netPrice = $variant->getRetailPrice() / (1 + ($tax/100));

        $this->log->info(sprintf('%s: Creating price %.2f for detail record %s.',
            __FUNCTION__,
            $netPrice,
            $detail->getNumber()
        ));

        $price = new Price();
        $price->setPrice($netPrice);
        $price->setFrom(1);
        $price->setTo(null);
        $customerGroup = $this->modelManager->getRepository(Group::class)->findOneBy(['key' => 'EK']);
        $price->setCustomerGroup($customerGroup);
        $price->setArticle($swArticle);
        $price->setDetail($detail);

        $this->modelManager->persist($price);
        return new ArrayCollection([$price]);
    }

    /**
     * If supplied $article has a supplier then get it by name from Shopware or create it if necessary.
     * Otherwise do the same with default supplier name InnoCigs
     *
     * @param Article $article
     * @return null|object|Supplier
     */
    protected function getSupplier(Article $article) {
        $supplierName = $article->getSupplier() ?? 'InnoCigs';
        $supplier = $this->modelManager->getRepository(Supplier::class)->findOneBy(['name' => $supplierName]);
        if (! $supplier) {
            $this->log->info(sprintf('%s: Creating Shopware supplier "%s"',
                __FUNCTION__,
                $supplierName
            ));
            $supplier = new Supplier();
            $this->modelManager->persist($supplier);
            $supplier->setName($supplierName);
        } else {
            $this->log->info(sprintf('%s: Using existing Shopware supplier "%s"',
                __FUNCTION__,
                $supplierName
            ));
        }
        return $supplier;
    }

    protected function deactivateShopwareArticle(Article $article) {
        $this->log->info('Remove Shopware ImportArticle for ' . $article->getName());
        return true;
    }

    protected function getTax(float $taxValue = 19.0) {
        $tax = $this->modelManager->getRepository(Tax::class)->findOneBy(['tax' => $taxValue]);
        if (! $tax instanceof Tax) {
            $name = sprintf('Tax (%.2f)', $taxValue);
            $this->log->info(sprintf('%s: Creating Shopware tax "%s" with tax value %.2f.',
                __FUNCTION__,
                $name,
                $taxValue
            ));

            $tax = new Tax();
            $this->modelManager->persist($tax);

            $tax->setName($name);
            $tax->setTax($taxValue);
        } else {
            $this->log->info(sprintf('%s: Using existing Shopware tax "%s" with tax value %.2f.',
                __FUNCTION__,
                $tax->getName(),
                $taxValue
            ));
        }
        return $tax;
    }

    public function handleActiveStateChange(Article $article)
    {
        $this->log->info(__CLASS__ . '#' . __FUNCTION__ . ' was triggered.');
        $result = $article->isActive() ?
            $this->createShopwareArticle($article) :
            $this->deactivateShopwareArticle($article);
        return $result;
    }
}