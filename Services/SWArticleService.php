<?php
/**
 * Created by PhpStorm.
 * User: katrin.sattler
 * Date: 08.11.2018
 * Time: 14:05
 */

namespace MxcDropshipInnocigs\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use MxcDropshipInnocigs\Exception\DatabaseException;
use MxcDropshipInnocigs\Models\InnocigsAttribute;
use MxcDropshipInnocigs\Models\InnocigsAttributeGroup;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Supplier;
use Zend\Log\Logger;

class SWArticleService{

    private $entityManager;
    private $log;

    public function __construct(EntityManager $entityManager,  Logger $log) {

        $this->log = $log;
        $this->log->info('Initializing Innocigs client.');
        $this->entityManager = $entityManager;
    }


    public function createSWEntries(){
        //get innocigs articles.
        $this->log->info('Start creating SW Entities');
        $icVariantRepository = $this->entityManager->getRepository(InnocigsVariant::class);

        // run only on active variants
        $icVariants = $icVariantRepository->findBy(['active' => true]);


        //mandatory article columns: supplier, details, $mainDetail
        foreach ($icVariants as $icVariant) {

            $this->log->info('icVariant:' . $icVariant->getCode());
            $icAttributes = $icVariant->getAttributes();

            $swOptions = $this->createSWOptions($icAttributes);
            $this->createSWDetail($icVariant, $swOptions);
        }

        return true;
    }

    private function createSWDetail(InnocigsVariant $icVariant, Collection $swOptions){
        $this->log->info(' Create Detail');

        $articleDetailRepository = $this->entityManager->getRepository(Detail::class);

        $this->log->info('Variant code: ' . $icVariant->getCode());

        $article = $articleDetailRepository->findOneBy(['number' => $icVariant->getCode()]);
        if(!isset($article)) {
            // @TODO: create Article, Detail and Set
        } else {
            $this->log->info('article(s) found');
        }

    }

    private function createSWOptions(Collection $icAttributes){
        $this->log->info(' Create Options');

        $swOptions = new ArrayCollection();

        $groupRepository = $this->entityManager->getRepository(Group::class);
        $allGroups = $groupRepository->findAll();

        $groupPosition = count($allGroups);
        $optionPosition = 0;
        $optionRepository = $this->entityManager->getRepository(Option::class);

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

            $this->entityManager->persist($swGroup);
            $this->entityManager->persist($swOption);
            try {
                $this->entityManager->flush();
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
        $icSupplier = $this->entityManager->getRepository(Supplier::class)->findBy(['name' => 'InnoCigs']);
        $this->log->info('FindBy result: '  . var_export($icSupplier, true));
    }

    protected function setupSWArticle() {
        $swArticle = new Article();
    }
}