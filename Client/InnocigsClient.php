<?php

namespace MxcDropshipInnocigs\Client;

use DateTime;
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

class InnocigsClient {

    private $apiClient = null;
    private $entityManager;
    private $attributes;
    private $log;

    // The maps below map names and codes retrieved from the API to vapee names and codes
    private $groupNameMap = [
        'STAERKE' => 'Nikotinstärke',
        'WIDERSTAND' => 'Widerstand',
        'PACKUNG' => 'Packungsgröße',
        'FARBE' => 'Farbe',
        'DURCHMESSER' => 'Durchmesser',
        'GLAS' => 'Glas',
    ];

    private $articleCodeMap = [];

    private $variantCodeMap = [];

    private $articleNameMap = [];

    private $attributeNameMap = [];

    public function __construct(EntityManager $entityManager, ApiClient $apiClient, Logger $log) {

        $this->log = $log;
        $this->log->info('Initializing Innocigs client.');
        $this->entityManager = $entityManager;
        $this->apiClient = $apiClient;
    }

    private function createVariantEntities(InnocigsArticle $article, array $variantArray) : array {
        $now = new DateTime();
        $articleProperties = null;
        // mark all variants of active articles active
        $active = $article->getActive();
        foreach ($variantArray as $variantCode => $variantData) {
            $variant = new InnocigsVariant();

            $variant->setInnocigsCode($variantCode);
            // use our code mapping if present, code from innocigs otherwise
            $variant->setCode($this->variantCodeMap[$variantCode] ?? $variantCode);
            $variant->setActive($active);
            $variant->setCreated($now);
            $variant->setUpdated($now);

            $tmp = $variantData['EAN'];
            // the API delivers an empty array instead of an empty string if EAN is not available
            $tmp = is_string($tmp) ? $tmp : '';
            $variant->setEan($tmp);
            $tmp = str_replace(',', '.', $variantData['PRODUCTS_PRICE']);
            $variant->setPriceNet(floatval($tmp));
            $tmp = str_replace(',', '.', $variantData['PRODUCTS_PRICE_RECOMMENDED']);
            $variant->setPriceRecommended(floatval($tmp));
            $article->addVariant($variant);
            if (null === $articleProperties) {
                // Innocigs variant names include variant descriptions
                // We take the first variant's name and remove the variant descriptions
                // in order to extract the real article name
                $articleName = $variantData['NAME'];
                foreach ($variantData['PRODUCTS_ATTRIBUTES'] as $attribute) {
                    $articleName = str_replace($attribute, '', $articleName);
                }
                $articleProperties['name'] = $articleName;
                $articleProperties['image'] = $variantData['PRODUCTS_IMAGE'];
            }
            foreach ($variantData['PRODUCTS_ATTRIBUTES'] as $group => $attribute) {
                $attrEntity = $this->attributes[$group][$attribute];
                $variant->addAttribute($attrEntity);
                $this->entityManager->persist($attrEntity);
            }
        }
        return $articleProperties;
    }

    private function createArticleEntities(array $articles) {
        $now = new DateTime();
        $i = 0;
        foreach ($articles as $articleCode => $articleData) {
            $article = new InnocigsArticle();
            // mark the first two articles active for testing
            $article->setActive($i < 2);
            $articleProperties = $this->createVariantEntities($article, $articleData);
            $name = $articleProperties['name'];
            $article->setInnocigsName($name);
            // use our name mapping if present, name from innocigs otherwise
            $article->setName($this->articleNameMap[$name] ?? $name);
            $article->setImage($articleProperties['image']);
            $article->setInnocigsCode($articleCode);
            // use our code mapping if present, code from innocigs otherwise
            $article->setCode($this->articleCodeMap[$articleCode] ?? $articleCode);
            $article->setDescription('n/a');
            $article->setUpdated($now);
            $article->setCreated($now);
            // this cascades persisting the variants also
            $this->entityManager->persist($article);
            try {
                $this->entityManager->flush();
            } catch (OptimisticLockException $e) {
                throw new DatabaseException('Doctrine failed to flush articles and variants: ' . $e->getMessage());
            }
            $i++;
            if ($i == 5) break;
        }
    }

    private function createAttributeEntities(InnocigsAttributeGroup $attributeGroup, $attributes) {
        $now = new DateTime();
        foreach ($attributes as $attributeName) {
            $attribute = new InnocigsAttribute();
            $attribute->setInnocigsName($attributeName);
            // use our name mapping if present, name from innocigs otherwise
            $attribute->setName($this->attributeNameMap[$attributeName] ?? $attributeName);
            $attribute->setCreated($now);
            $attribute->setUpdated($now);
            $attributeGroup->addAttribute($attribute);
            $this->attributes[$attributeGroup->getInnocigsName()][$attributeName] = $attribute;
        }
    }

    private function createAttributeGroupEntities(array $attrs)
    {
        $now = new DateTime();
        //$this->log->info(var_export($attrs, true));
        foreach ($attrs as $groupName => $attributes) {
            $attributeGroup = new InnocigsAttributeGroup();
            $attributeGroup->setInnocigsName($groupName);
            // use our name mapping if present, name from innocigs otherwise
            $attributeGroup->setName($this->groupNameMap[$groupName] ?? $groupName);
            $attributeGroup->setCreated($now);
            $attributeGroup->setUpdated($now);
            $this->createAttributeEntities($attributeGroup, array_keys($attributes));
            // this cascades persisting the attributes also
            $this->entityManager->persist($attributeGroup);
            try {
                $this->entityManager->flush();
            } catch (OptimisticLockException $e) {
                throw new DatabaseException('Doctrine failed to flush attributes and groups: ' . $e->getMessage());
            }
        }
    }

    public function downloadItems() {
        $raw = $this->apiClient->getItemList();
        $items = [];
        $attributes = [];

        foreach($raw['PRODUCTS']['PRODUCT'] as $item) {
            $items[$item['MASTER']][$item['MODEL']] = $item;
            foreach($item['PRODUCTS_ATTRIBUTES'] as $group => $attribute) {
                $attributes[$group][$attribute] = 1;
            }
        }
        $this->log->info('Creating attribute groups and attributes.');
        $this->createAttributeGroupEntities($attributes);
        $this->log->info('Creating articles and variants.');
        $this->createArticleEntities($items);
        $variantRepo = $this->entityManager->getRepository(InnocigsVariant::class);
        $activeVariants = $variantRepo->findBy(['active' => true]);
        $this->log->info('Active variants: ' . count($activeVariants));
    }

    public function createSWEntries(){
        //get innocigs articles.
        $this->log->info('Start creating SW Entities');
        $icVariantRepository = $this->entityManager->getRepository(InnocigsVariant::class);

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
