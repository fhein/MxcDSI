<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Supplier;
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
     * @var ArticleOptionMapper $attributeMapper
     */
    private $attributeMapper;

    /**
     * @var PropertyMapper $propertyMapper
     */
    private $propertyMapper;

    /**
     * @var array $unitOfWork
     */
    private $unitOfWork = [];

    private $shopwareGroups = [];
    private $shopwareGroupRepository = null;
    private $shopwareGroupLookup = [];

    public function __construct(ArticleOptionMapper $attributeMapper, PropertyMapper $propertyMapper, Logger $log) {
        $this->attributeMapper = $attributeMapper;
        $this->propertyMapper = $propertyMapper;
        $this->log = $log;
    }

    private function createShopwareArticle(InnocigsArticle $article) {

        $this->log->info('Create Shopware Article for ' . $article->getName());

        $swArticle = $this->getShopwareArticle($article);
        if (isset($swArticle))return $swArticle;

        $swArticle = new Article();
        $this->persist($swArticle);

        $this->attributeMapper->createShopwareGroupsAndOptions($article);
        $set = $this->attributeMapper->createConfiguratorSet($article, $swArticle);

        $tax = $this->getTax();
        $supplier = $this->getSupplier($article);
        $swArticle->setName($article->getName());
        $swArticle->setTax($tax);
        $swArticle->setSupplier($supplier);
        $swArticle->setConfiguratorSet($set);
        $swArticle->setMetaTitle('');
        $swArticle->setKeywords('');

        $swArticle->setDescription('');
        $swArticle->setDescriptionLong('');
        //todo: get description from innocigs

        $swArticle->setActive(true);

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

    private function createShopwareDetail(InnocigsVariant $variant, Article $swArticle, bool $isMainDetail){

    }

    private function getShopwareArticle(InnocigsArticle $article){
        $swArticle = null;
        $variants = $article->getVariants();

        //get first Shopware variant. If it exists, we suppose that the article and all other variants exist as well
        $this->log->info('Search for variant: ' .$variants{0}->getCode());
        $code = $this->propertyMapper->mapArticleCode($variants{0}->getCode());
        /**
         * @var Detail $swDetail
         */
        $swDetail = $this->getRepository(Detail::class)->findOneBy(['number' => $code]);

        if (null !== $swDetail) {
            $this->log->info('Detail found');
            $swArticle = $swDetail->getArticle();
        } else {
            $this->log->info('Detail not found');
        }

        return $swArticle;
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
        $this->log->info('Get Supplier');
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