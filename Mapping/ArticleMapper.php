<?php

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MxcDropshipInnocigs\Convenience\DoctrineModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsAttributeGroup;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
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
    use DoctrineModelManagerTrait;

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
        $article = new Article();
        $tax = new Tax();
    }

    private function removeShopwareArticle(InnocigsArticle $article) {
        $this->log->info('Remove Shopware Article for ' . $article->getName());
    }


    public function createSWEntries(){
        //get innocigs articles.
        $this->log->info('Start creating SW Entities');
        $icVariantRepository = $this->getRepository(InnocigsVariant::class);

        // run only on active variants
        $icVariants = $icVariantRepository->findBy(['active' => true]);

        foreach ($icVariants as $icVariant) {

            $this->log->info('icVariant:' . $icVariant->getCode());
            $icAttributes = $icVariant->getAttributes();

            $swOptions = $this->createSWOptions($icAttributes);
            $this->createSWDetail($icVariant, $swOptions);
        }

        return true;
    }

    private function createSWDetail(InnocigsVariant $icVariant, Collection $swOptions)
    {
        $this->log->info(' Create Detail');

        $articleDetailRepository = $this->getRepository(Detail::class);

        $this->log->info('Variant code: ' . $icVariant->getCode());

        $article = $articleDetailRepository->findOneBy(['number' => $icVariant->getCode()]);
        if (!isset($article)) {
            // @TODO: create Article, Detail and Set
        } else {
            $this->log->info('article(s) found');
        }
    }

    private function createSWOptions(Collection $icAttributes){
        $this->log->info(' Create Options');

        $swOptions = new ArrayCollection();

        $groupRepository = $this->getRepository(Group::class);
        $allGroups = $groupRepository->findAll();

        $groupPosition = count($allGroups);
        $optionPosition = 0;
        $optionRepository = $this->getRepository(Option::class);

        foreach($icAttributes as $icAttribute){
            /**
             * @var InnocigsAttributeGroup $icGroup
             */
            $icGroup = $icAttribute->getAttributeGroup();
            $icGroupName = $icGroup->getName();
            $icAttributeName = $icAttribute->getName();

            // check if we have a shopware group named like

            /**
             * @var Option $swOption
             *
             * to avoid code inspection 'undefined method'
             */
            //search for existing option entries
            $swOption = $optionRepository->findOneBy(['name' => $icAttributeName]);

            if(isset($swOption)){
                $swGroupName = $swOption->getGroup()->getName();
                if($swGroupName ==  $icGroupName){
                    $this->log->info('Option ' . $swOption->getName() . ' already exists');
                    $swOptions->add($swOption); // Group - Option Pair already exists
                    continue;
                }
            }

            //create Group
            $swGroup = $groupRepository->findOneBy(['name' => $icGroupName]);
            if (!isset($swGroup)){//create Group
                $swGroup = New Group();
                $swGroup->setName($icGroupName);
                $swGroup->setPosition($groupPosition += 1);
            }else{//get option position
                $persistedOptions = $optionRepository->findBy(['group' => $swGroup->getId()]);
                $optionPosition = count($persistedOptions) + 1;
            }

            //create Option
            $swOption = New Option();
            $swOption->setName($icAttributeName);
            $swOption->setPosition($optionPosition);
            $swOption->setGroup($swGroup);

            $this->persist($swGroup);
            $this->persist($swOption);
            $this->log->info('Option ' . $swOption->getName() . ' created');
            $swOptions->add($swOption);
        }
        $this->flush();
        $this->log->info('returning ' . count($swOptions) . ' options');

        return $swOptions;
    }

    protected function createSWSupplier() {
        $icSupplier = $this->getRepository(Supplier::class)->findBy(['name' => 'InnoCigs']);
        $this->log->info('FindBy result: '  . var_export($icSupplier, true));
    }

    protected function setupSWArticle() {
        $swArticle = new Article();
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