<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use Shopware\Models\Article\Article;
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
        $this->log = $log;
    }

    private function createShopwareArticle(InnocigsArticle $article) {
        $this->log->info('Create Shopware Article for ' . $article->getName());

        $configuratorSet = $this->attributeMapper->createConfiguratorSet($article);
        $tax = $this->getTax();
        $supplier = $this->getSupplier($article);

        $swArticle = new Article();

        /*** to be continued ...


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
        $supplierName = $article->getSupplier() ?? 'InnoCigs';
        $supplier = $this->getRepository(Supplier::class)->findOneBy(['name' => $supplierName]);
        if (! $supplier) {
            $supplier = new Supplier();
            $supplier->setName($supplierName);
            $this->persist($supplier);
            $this->flush();
        }
        return $supplier;
    }
}