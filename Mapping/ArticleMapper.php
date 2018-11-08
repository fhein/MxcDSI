<?php

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Zend\EventManager\EventInterface;
use MxcDropshipInnocigs\Exception\DatabaseException;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsAttributeGroup;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Supplier;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Log\Logger;

class ArticleMapper implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    private $modelManager;
    private $log;
    
    public function __construct(ModelManager $modelManager, Logger $log) {
        $this->modelManager = $modelManager;
        $this->log = $log;
    }

    private function createShopwareArticle(InnocigsArticle $article) {
        $this->log->info('Create Shopware Article for ' . $article->getName());
    }

    private function removeShopwareArticle(InnocigsArticle $article) {
        $this->log->info('Remove Shopware Article for ' . $article->getName());
    }

    public function onArticleActiveStateChanged(EventInterface $e) {
        /**
         * @var InnocigsArticle $article
         */
        $article = $e->getParams()['article'];
        $active = $article->isActive();
        $this->log->info('Article state changed to ' . var_export($article->isActive(), true));
        if ($active) {
            $this->createShopwareArticle($article);
        } else {
            $this->removeShopwareArticle($article);
        }
    }

    public function createSWEntries(){
        //get innocigs articles.
        $this->log->info('Start creating SW Entities');
        $icVariantRepository = $this->modelManager->getRepository(InnocigsVariant::class);

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

        $articleDetailRepository = $this->modelManager->getRepository(Detail::class);

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

        $groupRepository = $this->modelManager->getRepository(Group::class);
        $allGroups = $groupRepository->findAll();

        $groupPosition = count($allGroups);
        $optionPosition = 0;
        $optionRepository = $this->modelManager->getRepository(Option::class);

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

            $this->modelManager->persist($swGroup);
            $this->modelManager->persist($swOption);
            try {
                $this->modelManager->flush();
            } catch (OptimisticLockException $e) {
                throw new DatabaseException('Doctrine failed to flush shopware attributes and groups: ' . $e->getMessage());
            }
            $this->log->info('Option ' . $swOption->getName() . ' created');
            $swOptions->add($swOption);
        }
        $this->log->info('returning ' . count($swOptions) . ' options');

        return $swOptions;
    }

    protected function createSWSupplier() {
        $icSupplier = $this->modelManager->getRepository(Supplier::class)->findBy(['name' => 'InnoCigs']);
        $this->log->info('FindBy result: '  . var_export($icSupplier, true));
    }

    protected function setupSWArticle() {
        $swArticle = new Article();
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
    }
}