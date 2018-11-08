<?php

namespace MxcDropshipInnocigs\Client;

use Doctrine\ORM\OptimisticLockException;
use MxcDropshipInnocigs\Exception\DatabaseException;
use MxcDropshipInnocigs\Models\InnocigsAttribute;
use MxcDropshipInnocigs\Models\InnocigsAttributeGroup;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Components\Model\ModelManager;
use Zend\Log\Logger;

class InnocigsClient {

    /**
     * @var ApiClient $apiClient
     */
    private $apiClient;

    /**
     * @var ModelManager $modelManager
     */
    private $modelManager;

    /**
     * @var array $attributes
     */
    private $attributes;

    /**
     * @var PropertyMapper $mapper
     */
    private $mapper;

    /**
     * @var Logger $log
     */
    private $log;

    public function __construct(ModelManager $modelManager, ApiClient $apiClient, PropertyMapper $mapper, Logger $log) {

        $this->log = $log;
        $this->log->info('Initializing Innocigs client.');
        $this->modelManager = $modelManager;
        $this->apiClient = $apiClient;
        $this->mapper = $mapper;
    }

    private function createVariantEntities(InnocigsArticle $article, array $variantArray) : array {
        $articleProperties = null;
        // mark all variants of active articles active
        $active = $article->getActive();
        foreach ($variantArray as $variantCode => $variantData) {
            $variant = new InnocigsVariant();

            $variant->setInnocigsCode($variantCode);
            // use our code mapping if present, code from innocigs otherwise
            $variant->setCode($this->mapper->mapVariantCode($variantCode));
            $variant->setActive($active);

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
                $articleProperties['name'] = trim($articleName);
                $articleProperties['image'] = $variantData['PRODUCTS_IMAGE'];
            }
            foreach ($variantData['PRODUCTS_ATTRIBUTES'] as $group => $attribute) {
                $attrEntity = $this->attributes[$group][$attribute];
                $variant->addAttribute($attrEntity);
                $this->modelManager->persist($attrEntity);
            }
        }
        return $articleProperties;
    }

    private function createArticleEntities(array $articles) {
        $i = 0;
        foreach ($articles as $articleCode => $articleData) {
            $article = new InnocigsArticle();
            // mark the first two articles active for testing
            $article->setActive($i < 2);
            $articleProperties = $this->createVariantEntities($article, $articleData);
            $name = $articleProperties['name'];
            $article->setInnocigsName($name);
            // use our name mapping if present, name from innocigs otherwise
            $article->setName($this->mapper->mapArticleName($name));
            $article->setImage($articleProperties['image']);
            $article->setInnocigsCode($articleCode);
            // use our code mapping if present, code from innocigs otherwise
            $article->setCode($this->mapper->mapArticleCode($articleCode));
            $article->setDescription('n/a');
            // this cascades persisting the variants also
            $this->modelManager->persist($article);
            try {
                $this->modelManager->flush();
            } catch (OptimisticLockException $e) {
                throw new DatabaseException('Doctrine failed to flush articles and variants: ' . $e->getMessage());
            }
            $i++;
            if ($i == 5) break;
        }
    }

    private function createAttributeEntities(InnocigsAttributeGroup $attributeGroup, $attributes) {
        foreach ($attributes as $attributeName) {
            $attribute = new InnocigsAttribute();
            $attribute->setInnocigsName($attributeName);
            // use our name mapping if present, name from innocigs otherwise
            $attribute->setName($this->mapper->mapAttributeName($attributeName));
            $attributeGroup->addAttribute($attribute);
            $this->attributes[$attributeGroup->getInnocigsName()][$attributeName] = $attribute;
        }
    }

    private function createAttributeGroupEntities(array $attrs)
    {
        //$this->log->info(var_export($attrs, true));
        foreach ($attrs as $groupName => $attributes) {
            $attributeGroup = new InnocigsAttributeGroup();
            $attributeGroup->setInnocigsName($groupName);
            // use our name mapping if present, name from innocigs otherwise
            $attributeGroup->setName($this->mapper->mapAttributeGroupName($groupName));
            $this->createAttributeEntities($attributeGroup, array_keys($attributes));
            // this cascades persisting the attributes also
            $this->modelManager->persist($attributeGroup);
            try {
                $this->modelManager->flush();
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
        $variantRepo = $this->modelManager->getRepository(InnocigsVariant::class);
        $activeVariants = $variantRepo->findBy(['active' => true]);
        $this->log->info('Active variants: ' . count($activeVariants));
    }
}
