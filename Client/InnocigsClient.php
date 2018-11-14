<?php

namespace MxcDropshipInnocigs\Client;

use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsGroup;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Zend\Log\Logger;

class InnocigsClient {

    use ModelManagerTrait;

    /**
     * @var ApiClient $apiClient
     */
    private $apiClient;

    /**
     * @var array $options
     */
    private $options;

    /**
     * @var Logger $log
     */
    private $log;

    public function __construct(ApiClient $apiClient, Logger $log) {
        $this->log = $log;
        $this->log->info('Initializing Innocigs client.');
        $this->apiClient = $apiClient;
    }

    private function createVariants(InnocigsArticle $article, array $variantArray) : array {
        $articleProperties = null;
        // mark all variants of active articles active
        $active = $article->getActive();
        foreach ($variantArray as $variantCode => $variantData) {
            $variant = new InnocigsVariant();

            $variant->setCode($variantCode);
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
                foreach ($variantData['PRODUCTS_ATTRIBUTES'] as $option) {
                    $articleName = str_replace($option, '', $articleName);
                }
                $articleProperties['name'] = trim($articleName);
                $articleProperties['image'] = $variantData['PRODUCTS_IMAGE'];
            }
            foreach ($variantData['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $optionEntity = $this->options[$group][$option];
                $variant->addOption($optionEntity);
            }
        }
        return $articleProperties;
    }

    private function createArticles(array $articles) {
        $i = 0;
        foreach ($articles as $articleCode => $articleData) {
            $article = new InnocigsArticle();
            // mark the first two articles active for testing
            $article->setActive(false);
            $articleProperties = $this->createVariants($article, $articleData);
            $name = $articleProperties['name'];
            $article->setName($name);
            // use our name mapping if present, name from innocigs otherwise
            $article->setImage($articleProperties['image']);
            $article->setCode($articleCode);
            $article->setDescription('n/a');
            // this cascades persisting the variants also
            $this->persist($article);
            $i++;
            if ($i == 10) break;
        }
    }

    private function createOptions(InnocigsGroup $group, $options) {
        foreach ($options as $optionName) {
            $option = new InnocigsOption();
            $option->setName($optionName);
            $group->addOption($option);
            $this->options[$group->getName()][$optionName] = $option;
        }
    }

    private function createGroups(array $opts)
    {
        foreach ($opts as $groupName => $options) {
            $group = new InnocigsGroup();
            $group->setName($groupName);
            $this->createOptions($group, array_keys($options));
            // this cascades persisting the options also
            $this->persist($group);
        }
    }

    public function downloadItems() {
        $raw = $this->apiClient->getItemList();
        $items = [];
        $options = [];

        foreach($raw['PRODUCTS']['PRODUCT'] as $item) {
            $items[$item['MASTER']][$item['MODEL']] = $item;
            foreach($item['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $options[$group][$option] = 1;
            }
        }
        $this->log->info('Creating groups and options.');
        $this->createGroups($options);
        $this->log->info('Creating articles and variants.');
        $this->createArticles($items);
        $this->flush();
    }
}
