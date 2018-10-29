<?php

namespace MxcDropshipInnocigs\Client;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use MxcDropshipInnocigs\Helper\Log;
use MxcDropshipInnocigs\Models\InnocigsAttribute;
use MxcDropshipInnocigs\Models\InnocigsAttributeGroup;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;

class InnocigsClient {

    private $apiClient = null;
    private $user;
    private $password;
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

    public function __construct(EntityManager $entityManager, string $user = null, string $password = null) {

        $this->log = new Log();
        $this->log->log('Initializing Innocigs client.');
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->password = $password;
    }

    private function createVariantEntities(InnocigsArticle $article, array $variantArray) : array {
        $now = new DateTime();
        $articleProperties = null;
        foreach ($variantArray as $variantCode => $variantData) {
            $variant = new InnocigsVariant();

            $variant->setInnocigsCode($variantCode);
            // use our code mapping if present, code from innocigs otherwise
            $variant->setCode($this->variantCodeMap[$variantCode] ?? $variantCode);
            $variant->setActive(false);
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
            // @TODO: check whether the images of the different variants are different, actually. If they are the same, this property belongs to article and should be discarded here
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
            $article->setActive(false);
            $article->setUpdated($now);
            $article->setCreated($now);
            // this cascades persisting the variants also
            $this->entityManager->persist($article);
            try {
                $this->entityManager->flush();
            } catch (OptimisticLockException $e) {
                // @todo: add error handling here
                $this->log->log('Exception thrown in createEntities.');
                return false;
            }
            $i++;
            if ($i == 5) break;
        }
        return true;
    }

    private function createAttributeGroupEntities(array $attrs)
    {
        $now = new DateTime();
        $this->log->log(var_export($attrs, true));
        foreach ($attrs as $groupName => $attributes) {
            $attributeGroup = new InnocigsAttributeGroup();
            $attributeGroup->setInnocigsName($groupName);
            // use our name mapping if present, name from innocigs otherwise
            $attributeGroup->setName($this->groupNameMap[$groupName] ?? $groupName);
            $attributeGroup->setCreated($now);
            $attributeGroup->setUpdated($now);
            foreach ($attributes as $attributeName => $_) {
                $attribute = new InnocigsAttribute();
                $attribute->setInnocigsName($attributeName);
                // use our name mapping if present, name from innocigs otherwise
                $attribute->setName($this->attributeNameMap[$attributeName] ?? $attributeName);
                $attribute->setCreated($now);
                $attribute->setUpdated($now);
                $attributeGroup->addAttribute($attribute);
                $this->attributes[$groupName][$attributeName] = $attribute;
            }
            // this cascades persisting the attributes also
            $this->entityManager->persist($attributeGroup);
            try {
                $this->entityManager->flush();
            } catch (OptimisticLockException $e) {
                // @todo: add error handling here
                $this->log->log('Exception thrown in createAttributeGroups.');
                return false;
            }
        }
        return true;
    }

    public function downloadItems() {
        $response = $this->getApiClient()->getItemList();
        if (!$response->isSuccess()) return false;

        $body = simplexml_load_string($response->getBody());
        $raw = json_decode(json_encode($body), TRUE);

        $items = [];
        $attributes = [];

        foreach($raw['PRODUCTS']['PRODUCT'] as $item) {
            $items[$item['MASTER']][$item['MODEL']] = $item;
            foreach($item['PRODUCTS_ATTRIBUTES'] as $group => $attribute) {
                $attributes[$group][$attribute] = 1;
            }
        }
        $this->log->log('Creating Entities.');
        if (!$this->createAttributeGroupEntities($attributes)) return false;
        return $this->createArticleEntities($items);
    }

    private function getApiClient() {
        if (null === $this->apiClient) {
            $this->apiClient = new ApiClient($this->user, $this->password);
        }
        return $this->apiClient;
    }
}
