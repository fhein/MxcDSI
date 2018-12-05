<?php

namespace MxcDropshipInnocigs\Listener;

use Mxc\Shopware\Plugin\ActionListener;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsGroup;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use Zend\EventManager\EventInterface;

class InnocigsClient extends ActionListener {
    /**
     * @var ApiClient $apiClient
     */
    protected $apiClient;
    /**
     * @var array $options
     */
    protected $options;
    /**
     * @var LoggerInterface $log
     */
    protected $log;
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        Config $config,
        LoggerInterface $log
    ) {
        parent::__construct($config, $log);
        $this->apiClient = $apiClient;
        $this->modelManager = $modelManager;
    }

    private function getStringParam($value) {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value) && empty($value)) {
            return '';
        }
        throw new InvalidArgumentException(
            sprintf('String or empty array expected, got %s.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    protected function createVariants(InnocigsArticle $article, array $variantArray) : array {
        $articleProperties = null;
        // mark all variants of active articles active
        $active = $article->getActive();
        $ignored = $article->getIgnored();
        foreach ($variantArray as $variantCode => $variantData) {
            $variant = new InnocigsVariant();

            $variant->setCode($variantCode);
            $variant->setActive($active);
            $variant->setIgnored($ignored);

            $variant->setEan($this->getStringParam($variantData['EAN']));
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

    protected function createArticles(array $articles, int $limit = -1) {
        $i = 0;
        foreach ($articles as $articleCode => $articleData) {
            $article = new InnocigsArticle();
            $article->setActive(false);
            $article->setIgnored(false);
            $articleProperties = $this->createVariants($article, $articleData);
            $name = $articleProperties['name'];
            $article->setName($name);
            $article->setImage($this->getStringParam($articleProperties['image']));
            $article->setCode($articleCode);
            $article->setDescription('n/a');
            // this cascades persisting the variants also
            $this->modelManager->persist($article);
            $i++;
            if ($limit !== -1 && $i === $limit) break;
        }
    }

    protected function createOptions(InnocigsGroup $group, $options) {
        foreach ($options as $optionName) {
            $option = new InnocigsOption();
            $option->setName($optionName);
            $group->addOption($option);
            $this->options[$group->getName()][$optionName] = $option;
        }
    }

    protected function createGroups(array $opts)
    {
        foreach ($opts as $groupName => $options) {
            $group = new InnocigsGroup();
            $group->setName($groupName);
            $this->createOptions($group, array_keys($options));
            // this cascades persisting the options also
            $this->modelManager->persist($group);
        }
    }

    public function addArticleDetail(InnocigsArticle $article)
    {
        $variant = $article->getVariants()[0];
        $raw = $this->apiClient->getItemInfo($variant->getCode());
        $description = $this->getStringParam($raw['PRODUCTS']['PRODUCT']['DESCRIPTION']);
        if ($description === '') {
            $this->log->warn(sprintf('%s: No description available from InnoCigs for article %s.',
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
            $this->modelManager->persist($article);
        } else  {
            $this->log->info(sprintf('%s: Article description from InnoCigs for article %s is up to date.',
                __FUNCTION__,
                $article->getCode()
            ));
        }
    }

    public function createArticleConfigurationFile() {
        $articles = $this->modelManager->getRepository(InnocigsArticle::class)->findAll();
        $config = [];

        foreach ($articles as $article) {
            $config[$article->getCode()] = [
                'name' => $article->getName(),
                'brand' => $article->getBrand(),
                'supplier' => $article->getSupplier(),
            ];
        }
        $content = '<?php' . PHP_EOL . 'return ' . var_export($config, true). ';' . PHP_EOL;
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
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    public function activate(EventInterface $e) {
        $this->log->enter();
        $context = $e->getParam('context');
        $options = $this->getOptions();
        $articles = $this->modelManager->getRepository(InnocigsArticle::class)->findAll();
        if (empty($articles)) {
            $this->importArticles($options->numberOfArticles ?? -1);
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

    /**
     * @noinspection PhpUnusedParameterInspection
     * @param EventInterface $e
     * @return bool
     */
    public function deactivate(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e) {
        return true;
    }
}
