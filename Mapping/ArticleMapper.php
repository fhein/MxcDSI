<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Application\Application;
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

    protected $services;

    /**
     * @var ArticleOptionMapper $attributeMapper
     */
    private $attributeMapper;

    /**
     * @var array $unitOfWork
     */
    private $unitOfWork = [];

    private $shopwareGroups = [];
    private $shopwareGroupRepository = null;
    private $shopwareGroupLookup = [];

    public function __construct(ArticleOptionMapper $attributeMapper, Logger $log) {
        $this->attributeMapper = $attributeMapper;
        $this->services = Application::getServices();
        $this->log = $log;
    }

    private function createShopwareArticle(InnocigsArticle $article) {

        $this->log->info('Create Shopware Article for ' . $article->getName());
        $this->attributeMapper->createShopwareGroupsAndOptions($article);
    }

    private function createShopwareDetail(InnocigsVariant $variant){

    }

    private function getShopwareArticle(InnocigsArticle $article){
        $variants = $article->getVariants();

        //get first Shopware variant. If it exists, we suppose that the article and all other variants exist as well
        $this->log->info('Search for variant: ' .$variants{0}->getCode());
        $swDetail = $this->getRepository(Detail::class)->findOneBy(['number' => $variants{0}->getCode()]);

        if (isset($swDetail)){
            $this->log->info('Detail found');
            $swArticle = $swDetail->getArticle();
        }else{
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