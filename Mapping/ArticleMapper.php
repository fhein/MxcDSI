<?php

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Customer\Group;
use Shopware\Models\Media\Media;
use Shopware\Models\Tax\Tax;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Log\Logger;

class ArticleMapper implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;
    use ModelManagerTrait;

    /**
     * @var Logger $log
     */
    private $log;

    /**
     * @var ArticleOptionMapper $optionMapper
     */
    private $optionMapper;

    /**
     * @var PropertyMapper $propertyMapper
     */
    private $propertyMapper;

    /**
     * @var MediaService $mediaService
     */
    private $mediaService;

    /**
     * @var array $unitOfWork
     */
    private $unitOfWork = [];

    private $shopwareGroups = [];
    private $shopwareGroupRepository = null;
    private $shopwareGroupLookup = [];

    public function __construct(
        ArticleOptionMapper $option,
        PropertyMapper $propertyMapper,
        MediaService $mediaService,
        Logger $log)
    {
        $this->optionMapper = $option;
        $this->propertyMapper = $propertyMapper;
        $this->mediaService = $mediaService;
        $this->log = $log;
    }

    private function createShopwareArticle(InnocigsArticle $article) {

        $swArticle = $this->getShopwareArticle($article);

        if ($swArticle instanceof Article) {
            $this->log->info(sprintf('%s: InnoCigs Article %s is already mapped to Shopware Article (record id: %s).',
                __FUNCTION__,
                $article->getCode(),
                $swArticle->getId()
            ));
            return $swArticle;
        }

        $name = $this->propertyMapper->mapArticleName($article->getName());
        $this->log->info(sprintf('%s: Creating Shopware article "%s" for InnoCigs article number %s.',
            __FUNCTION__,
            $name,
            $article->getCode()
        ));

        $swArticle = new Article();
        $this->persist($swArticle);

        $tax = $this->getTax();
        $supplier = $this->getSupplier($article);
        $swArticle->setName($name);
        $swArticle->setTax($tax);
        $swArticle->setSupplier($supplier);
        $swArticle->setMetaTitle('');
        $swArticle->setKeywords('');
        $swArticle->setDescription('');
        $swArticle->setDescriptionLong('');
        //todo: get description from innocigs

        $swArticle->setActive(true);

        $this->optionMapper->createShopwareGroupsAndOptions($article);
        $set = $this->optionMapper->createConfiguratorSet($article, $swArticle);
        $swArticle->setConfiguratorSet($set);

        //        $this->createImage($article);


        //create details from innocigs variants
        $variants = $article->getVariants();

        $isMainDetail = true;
        foreach($variants as $variant){
            /**
             * @var Detail $swDetail
             */
            $swDetail = $this->createShopwareDetail($variant, $swArticle, $isMainDetail);
            if($isMainDetail){
                $swArticle->setMainDetail($swDetail);
                $this->persist($swArticle);
                $isMainDetail = false;
            }
        }

        $this->flush();
        return $swArticle;
    }

    /**
     * Gets the Shopware Article by looking for the Shopware detail of the first variant for the supplied $article.
     * If it exists, we suppose that the article and all other variants exist as well
     *
     * @param InnocigsArticle $article
     * @return null|Article
     */
    private function getShopwareArticle(InnocigsArticle $article){
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
        $swDetails = $this->getRepository(Detail::class)->matching($criteria);

        if (! $swDetails->isEmpty()){
            $swArticle = $swDetails->offsetGet(0)->getArticle();
        }
        return $swArticle;
    }

    private function createAttribute(Detail $swDetail) {
        //create attribute - Articles will not be displayed if attribute entry is missing
        $this->log->info(sprintf('%s: Creating attribute record for detail record %s',
            __FUNCTION__,
            $swDetail->getNumber()
        ));
        $attribute = new \Shopware\Models\Attribute\Article();
        $this->persist($attribute);
        $attribute->setArticleDetail($swDetail);
    }

    private function createShopwareDetail(InnocigsVariant $variant, Article $swArticle, bool $isMainDetail){
        $this->log->info(sprintf('%s: Creating detail record for InnoCigs variant %s',
            __FUNCTION__,
            $variant->getCode()
        ));

        $detail = new Detail();
        $this->persist($detail);

        $detail->setNumber($this->propertyMapper->mapVariantCode($variant->getCode()));
        $detail->setEan($variant->getEan());
        $detail->setStockMin(0);
        $detail->setSupplierNumber('');
        $detail->setAdditionalText('');
        $detail->setPackUnit('');
        $detail->setShippingTime('');

        $isMainDetail ? $detail->setKind(1) : $detail->setKind(2);

        $detail->setActive(true);
        $detail->setLastStock(0);
        // Todo: $detail->setPurchaseUnit();
        // Todo: $detail->setReferenceUnit();

        $detail->setArticle($swArticle);

        $prices = $this->createPrice($variant, $swArticle, $detail);
        $detail->setPrices($prices);
        $detail->setConfiguratorOptions(new ArrayCollection($variant->getShopwareOptions()));

        $this->createAttribute($detail);
        return $detail;
    }

    private function createImage(InnocigsArticle $article)
    {
        //download image
        $url = $article->getImage();
        $urlInfo = pathinfo($url);
        $swUrl = 'media/image/' . $urlInfo['basename'];
        $this->log->info('download image from '.$url);

        $fileContent = file_get_contents($url);

        // save to filesystem
        $this->log->info('save image to '. $swUrl);

        $this->mediaService->write($swUrl); // second parameter needed

        //create database entry

        $media = new Media();
        $media->setAlbumId(-1);
        $media->setName($urlInfo['filename']);
        $media->setPath($swUrl);
        $media->setType('IMAGE');
        $media->setExtension($urlInfo['extension']);

        $size = getimagesize($swUrl);

        $this->log->info('Image width: '. $size{0}. ' height: '. $size{1});

        $media->setWidth($size{0});
        $media->setHeight{$size{1}};
        $media->setFileSize(filesize($swUrl));

        $this->persist($media);

        $image = new Image();
        $image->setMedia($media);
    }

    private function createPrice(InnocigsVariant $variant, Article $swArticle, Detail $detail){
        $tax = $this->getTax()->getTax();
        $netPrice = $variant->getPriceRecommended() / (1 + ($tax/100));

        $this->log->info(sprintf('%s: Creating price %.2f for detail record %s.',
            __FUNCTION__,
            $netPrice,
            $detail->getNumber()
        ));

        $price = new Price();
        $price->setPrice($netPrice);
        $price->setFrom(1);
        $price->setTo(null);
        $customerGroup = $this->getRepository(Group::class)->findOneBy(['key' => 'EK']);
        $price->setCustomerGroup($customerGroup);
        $price->setArticle($swArticle);
        $price->setDetail($detail);

        $this->persist($price);
        return new ArrayCollection([$price]);
    }


    private function removeShopwareArticle(InnocigsArticle $article) {
        $this->log->info('Remove Shopware Article for ' . $article->getName());
    }

    public function onArticleActiveStateChanged(EventInterface $e) {
        /**
         * @var InnocigsArticle $article
         */
        $this->unitOfWork[] = $e->getParams()['article'];
    }

    public function onProcessActiveStates()
    {

        foreach ($this->unitOfWork as $article) {
            /**
             * @var InnocigsArticle $article
             */
            $this->log->info(sprintf('%s: Processing active state for %s.',
                __FUNCTION__,
                $article->getCode()
            ));
            $article->isActive() ?
                $this->createShopwareArticle($article) :
                $this->removeShopwareArticle($article);
        }
        $this->unitOfWork = [];
        // we have to reset this because groups may be deleted
        // by other modules or plugins
    }

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     * @param int $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('article_active_state_changed', [$this, 'onArticleActiveStateChanged'], $priority);
        $this->listeners[] = $events->attach('process_active_states', [$this, 'onProcessActiveStates'], $priority);
    }

    private function getTax(float $taxValue = 19.0) {
        $tax = $this->getRepository(Tax::class)->findOneBy(['tax' => $taxValue]);
        if (! $tax instanceof Tax) {
            $name = sprintf('Tax (%.2f)', $taxValue);
            $this->log->info(sprintf('%s: Creating Shopware tax "%s" with tax value %.2f.',
                __FUNCTION__,
                $name,
                $taxValue
            ));

            $tax = new Tax();
            $this->persist($tax);

            $tax->setName($name);
            $tax->setTax($taxValue);
        } else {
            $this->log->info(sprintf('%s: Using existing Shopware tax "%s" with tax value %.2f.',
                __FUNCTION__,
                $tax->getName()
            ));

        }
        return $tax;
    }

    /**
     * If supplied $article has a supplier then get it by name from Shopware or create it if necessary.
     * Otherwise do the same with default supplier name InnoCigs
     *
     * @param InnocigsArticle $article
     * @return null|object|Supplier
     */

    private function getSupplier(InnocigsArticle $article) {
        $supplierName = $article->getSupplier() ?? 'InnoCigs';
        $supplier = $this->getRepository(Supplier::class)->findOneBy(['name' => $supplierName]);
        if (! $supplier) {
            $this->log->info(sprintf('%s: Creating Shopware supplier "%s"',
                __FUNCTION__,
                $supplierName
            ));
            $supplier = new Supplier();
            $this->persist($supplier);
            $supplier->setName($supplierName);
        } else {
            $this->log->info(sprintf('%s: Using existing Shopware supplier "%s"',
                __FUNCTION__,
                $supplierName
            ));
        }
        return $supplier;
    }
}