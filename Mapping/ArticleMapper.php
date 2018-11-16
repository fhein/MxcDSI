<?php

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Image;
use Shopware\Models\Article\Price;
use Shopware\Models\Article\Supplier;
use Shopware\Models\Tax\Tax;
use Shopware\Models\Customer\Group;
use Shopware\Models\Media;
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

    protected $services;

    /**
     * @var ArticleAttributeMapper $attributeMapper
     */
    private $attributeMapper;

    /**
     * @var array $unitOfWork
     */
    private $unitOfWork = [];

    private $shopwareGroups = [];
    private $shopwareGroupRepository = null;
    private $shopwareGroupLookup = [];


    public function __construct(ArticleAttributeMapper $attributeMapper, Logger $log) {
        $this->attributeMapper = $attributeMapper;
        $this->services = Application::getServices();
        $this->log = $this->services->get('logger');
        //$this->log = $log;
    }

    private function createShopwareArticle(InnocigsArticle $article) {

            $swArticle = $this->getShopwareArticle($article);

            if (isset($swArticle))return $swArticle;

            $this->log->info('Create Shopware Article for ' . $article->getName());

            // Components you need to create a shopware article
            $tax = $this->getTax();
            $supplier = $this->getSupplier($article);
            //$configuratorSet = $this->attributeMapper->createConfiguratorSet($article);

            $swArticle = new Article();
            $swArticle->setName($article->getName());
            $swArticle->setTax($tax);
            $swArticle->setSupplier($supplier);
            //$swArticle->setConfiguratorSet($configuratorSet);
            $swArticle->setMetaTitle('');
            $swArticle->setKeywords('');

            $swArticle->setDescription('');
            $swArticle->setDescriptionLong('');
            //todo: get description from innocigs

            $swArticle->setActive(true);

    //        $this->createImage($article);

            $this->persist($swArticle);

            //create details from innocigs variants
            $variants = $article->getVariants();

            $isMainDetail = true;
            foreach($variants as $variant){
                $detail = $this->createShopwareDetail($variant, $swArticle, $isMainDetail);
                if($isMainDetail){
                    $swArticle->setMainDetail($detail);
                    $this->persist($swArticle);
                    $isMainDetail = false;
                }
            }

            $this->flush();
            return $swArticle;

    }

    private function createAttribute(Detail $detail){
        //create attribute - Articles will not be displayed if attribute entry is missing
        $this->log->info('create Attribute for detail: '.$detail->getNumber());
        $attribute = new \Shopware\Models\Attribute\Article();
        $attribute->setArticleDetail($detail);
        $this->persist($attribute);
    }

    private function createShopwareDetail(InnocigsVariant $variant, Article $swArticle, bool $isMainDetail){
        $this->log->info('create Detail: '.$variant->getCode());

        $detail = new Detail();
        $detail->setNumber($variant->getCode());
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

        $this->persist($detail);

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
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $this->log->info('save image to '. $swUrl);

        $mediaService->write($swUrl);

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

        $this->log->info('create price '.$netPrice.' for detail: '. $detail->getNumber());

        $price = new Price();
        $price->setPrice($netPrice);
        $price->setFrom(1);
        $price->setTo(null);
        $price->setCustomerGroup($this->getCustomerGroup());
        $price->setArticle($swArticle);
        $price->setDetail($detail);

        $this->persist($price);
        return new ArrayCollection([$price]);
    }

    private function getCustomerGroup(string $customerGroupKey = 'EK') {
        return $this->getRepository(Group::class)->findOneBy(['key' => $customerGroupKey]);
    }

    /**
     * Gets the Shopware Article by looking for the Shopware detail of the first variant for the supplied $article.
     * If it exists, we suppose that the article and all other variants exist as well
     *
     * @param InnocigsArticle $article
     * @return null|Article
     */
    private function getShopwareArticle(InnocigsArticle $article){
        $variants = $article->getVariants();

        $swDetail = $this->getRepository(Detail::class)->findOneBy(['number' => $variants{0}->getCode()]);

        if (isset($swDetail)){
            $this->log->info('Article already exists');
            $swArticle = $swDetail->getArticle();
        }

        return $swArticle;
    }

    /**
     * If supplied $article exists, the Shopware article and all related details, attributes and prices are removed from database.
     *
     * @param InnocigsArticle $article
     */
    private function removeShopwareArticle(InnocigsArticle $article) {
        $this->log->info('Remove Shopware Article for ' . $article->getName());

        //Todo: remove Set?, group?, option?
        $swArticle = $this->getShopwareArticle($article);

        if (isset($swArticle)){
            $details = $swArticle->getDetails();
            foreach($details as $detail){
                $attributes = $detail->getAttribute();
                if(isset($attributes)) $this->remove($attributes);

                $prices = $detail->getPrices;
                foreach($prices as $price){
                    $this->remove($price);
                }

                $this->remove($detail);
            }
            $this->remove($swArticle);
            $this->flush();
            $this->log->info('Article removed successfully');
        }

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
            $this->log->info('------------------------------------------------------------------------------------------------------');
            $this->log->info('Processing active state for ' . $article->getCode());
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

    private function getTax(float $tax = 19.0) {
        return $this->getRepository(Tax::class)->findOneBy(['tax' => $tax]);
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
            if (!$supplier) {
                $supplier = new Supplier();
                $supplier->setName($supplierName);
                $this->persist($supplier);
                $this->flush();
            }
            return $supplier;
    }
}