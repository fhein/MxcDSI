<?php

namespace MxcDropshipInnocigs\Client;

use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsGroup;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use MxcDropshipInnocigs\Plugin\ActionListener;
use MxcDropshipInnocigs\Plugin\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Plugin\Service\LoggerInterface;
use Zend\Config\Config;
use Zend\EventManager\EventInterface;

class InnocigsClient extends ActionListener {

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

    public function __construct(ApiClient $apiClient, Config $config, LoggerInterface $log) {
        $this->log = $log;
        $this->apiClient = $apiClient;
        parent::__construct($config);
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

    private function createArticles(array $articles, int $limit = -1) {
        $i = 0;
        foreach ($articles as $articleCode => $articleData) {
            $article = new InnocigsArticle();
            // mark the first two articles active for testing
            $article->setActive(false);
            $articleProperties = $this->createVariants($article, $articleData);
            $name = $articleProperties['name'];
            $article->setName($name);
            // use our name mapping if present, name from innocigs otherwise
            $tmp = is_string($articleProperties['image']) ? $articleProperties['image'] : '';
            $article->setImage($tmp);
            $article->setCode($articleCode);
            $article->setDescription('n/a');
            // this cascades persisting the variants also
            $this->persist($article);
            $i++;
            if ($limit !== -1 && $i === $limit) break;
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

    public function addArticleDetail(InnocigsArticle $article)
    {
        $variant = $article->getVariants()[0];
        $raw = $this->apiClient->getItemInfo($variant->getCode());
        $description = $raw['PRODUCTS']['PRODUCT']['DESCRIPTION'];
        if (! is_string($description)) {
            $this->log->info(sprintf('%s: No description available from InnoCigs for article %s.',
                __FUNCTION__,
                $article->getCode()
            ));
            return;
        }
        if ($article->getDescription() !== $description) {
            $this->log->info(sprintf('%s: Adding article description from InnoCigs to article %s.',
                __FUNCTION__,
                $article->getCode()
            ));
            $article->setDescription($description);
            $this->persist($article);
        } else  {
            $this->log->info(sprintf('%s: Article description from InnoCigs for article %s is up to date.',
                __FUNCTION__,
                $article->getCode()
            ));
        }
    }

    public function createArticleConfigurationFile() {
        $articles = $this->getRepository(InnocigsArticle::class)->findAll();
        $config = [];

        foreach ($articles as $article) {
            $config[$article->getCode()] = [
                'name' => $article->getName(),
                'brand' => $article->getBrand(),
                'supplier' => $article->getSupplier(),
            ];
        }
        $content = '<?php ' . PHP_EOL . 'return ' . var_export($config, true). ';' . PHP_EOL;
        file_put_contents(__DIR__ . '/../Config/article.config.php', $content);
    }

    public function importArticles(int $limit = -1) {
        $raw = $this->apiClient->getItemList();
        $items = [];
        $options = [];
        foreach($raw['PRODUCTS']['PRODUCT'] as $item) {
            $items[$item['MASTER']][$item['MODEL']] = $item;
            foreach($item['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $options[$group][$option] = 1;
            }
            if ($limit !== -1 && count($items) === $limit) break;
        }
        $this->log->info('Creating groups and options.');
        $this->createGroups($options);
        $this->log->info('Creating articles and variants.');
        $this->createArticles($items, $limit);
        $this->flush();
    }

    public function onActivate(EventInterface $e) {
        $this->log->enter();
        $context = $e->getParam('context');
        $options = $this->getOptions();
        if (true === $options->importArticles) {
            $this->importArticles($options->onActivate->numberOfArticles ?? -1);
        }
        if (true === $options->saveArticleConfiguration){
            $this->createArticleConfigurationFile();
        }
        if (true === $options->clearCache) {
            $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        }
        $this->log->leave();
        return true;
    }

    public function onDeactivate(EventInterface $e) {
        $this->log->enter();
        $options = $this->getOptions();
        if (true === $options->dropArticles) {
            // @todo: Drop Articles
            if (true === $options->dropConfigurator) {
                // @todo: Drop Groups and Options also
            }
        }
        $this->log->leave();
        return true;
    }
}
