<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsGroup;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use Zend\Config\Factory;

class InnocigsClient
{
    /**
     * @var string $articleConfigFile
     */
    protected $articleConfigFile = __DIR__ . '/../Config/article.config.php';
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

    /**
     * @var array $articleConfig
     */
    protected $articleConfig = [];

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * InnocigsClient constructor.
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->apiClient = $apiClient;
        $this->config = $config;
        $this->log = $log;
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
        } else {
            $this->log->info(sprintf('%s: Article description from InnoCigs for article %s is up to date.',
                __FUNCTION__,
                $article->getCode()
            ));
        }
    }

    private function getStringParam($value)
    {
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

    protected function createGroups(array $opts)
    {
        foreach ($opts as $groupName => $options) {
            $group = new InnocigsGroup();
            $group->setName($groupName);
            $group->setAccepted(true);
            $this->createOptions($group, array_keys($options));
            // this cascades persisting the options also
            $this->modelManager->persist($group);
        }
    }

    protected function createOptions(InnocigsGroup $group, $options)
    {
        foreach ($options as $optionName) {
            $option = new InnocigsOption();
            $option->setName($optionName);
            $option->setAccepted(true);
            $group->addOption($option);
            $this->options[$group->getName()][$optionName] = $option;
        }
    }

    protected function createArticles(array $articles, int $limit = -1)
    {
        $i = 0;
        foreach ($articles as $articleCode => $articleData) {
            $article = new InnocigsArticle();
            $article->setActive(false);
            $article->setAccepted(true);
            $articleProperties = $this->createVariants($article, $articleData);
            $name = $articleProperties['name'];
            $article->setName($name);
            $article->setImageUrl($this->getStringParam($articleProperties['image']));
            $article->setManualUrl($this->getStringParam($articleProperties['manual']));
            $article->setCode($articleCode);
            $article->setDescription('n/a');
            if (isset($this->articleConfig[$articleCode]['brand'])) {
                $article->setBrand($this->articleConfig[$articleCode]['brand']);
            } else {
                $this->log->warn(sprintf('No brand info for article %s: %s',
                    $articleCode,
                    $name)
                );
            }
            if (isset($this->articleConfig[$articleCode]['supplier'])) {
                $article->setSupplier($this->articleConfig[$articleCode]['supplier']);
            } else {
                $this->log->warn(sprintf('No supplier info for article %s: %s',
                    $articleCode,
                    $name)
                );
            }
            // this cascades persisting the variants also
            $this->modelManager->persist($article);
            $i++;
            if ($limit !== -1 && $i === $limit) {
                break;
            }
        }
    }

    protected function createVariants(InnocigsArticle $article, array $variantArray): array
    {
        $articleProperties = null;
        // mark all variants of active articles active
        $active = $article->isActive();
        $accepted = $article->isAccepted();
        foreach ($variantArray as $variantCode => $variantData) {
            $variant = new InnocigsVariant();

            $variant->setCode($variantCode);
            $variant->setActive($active);
            $variant->setAccepted($accepted);

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
                $articleProperties['manual'] = $variantData['PRODUCTS_MANUAL'];

            }
            foreach ($variantData['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $optionEntity = $this->options[$group][$option];
                $variant->addOption($optionEntity);
            }
        }
        return $articleProperties;
    }

    public function createArticleConfiguration()
    {
        $articles = $this->modelManager->getRepository(InnocigsArticle::class)->findAll();
        $config = [];

        foreach ($articles as $article) {
            $config[$article->getCode()] = [
                'name' => $article->getName(),
                'brand' => $article->getBrand(),
                'supplier' => $article->getSupplier(),
            ];
        }
        Factory::toFile($this->articleConfigFile, $config);
    }

    protected function readArticleConfiguration() {
        $this->articleConfig = [];
        if (file_exists($this->articleConfigFile)) {
            /** @noinspection PhpIncludeInspection */
            $this->articleConfig = include $this->articleConfigFile;
        }
    }

    public function importArticles(int $limit = -1)
    {
        $raw = $this->apiClient->getItemList();
        $items = [];
        $options = [];
        foreach ($raw['PRODUCTS']['PRODUCT'] as $item) {
            $items[$item['MASTER']][$item['MODEL']] = $item;
            foreach ($item['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $options[$group][$option] = 1;
            }
            if ($limit !== -1 && count($items) === $limit) {
                break;
            }
        }
        $this->log->info('Creating groups and options.');
        $this->createGroups($options);
        $this->log->info('Creating articles and variants.');
        $this->createArticles($items, $limit);
        $this->modelManager->flush();
    }

    public function import()
    {
        $this->log->enter();
        // only import articles if we do not have them
        $repository = $this->modelManager->getRepository(InnocigsArticle::class);
        $count = intval($repository->createQueryBuilder('a')->select('count(a.id)')->getQuery()->getSingleScalarResult());
        if ($count === 0) {
            /** @noinspection PhpUndefinedFieldInspection */
            if (true === $this->config->useArticleConfiguration) {
                $this->readArticleConfiguration();
            }
            /** @noinspection PhpUndefinedFieldInspection */
            $this->importArticles($this->config->numberOfArticles ?? -1);
        }
        $this->log->leave();
        return true;
    }
}
