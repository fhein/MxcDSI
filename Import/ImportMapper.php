<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Exception\InvalidArgumentException;
use MxcDropshipInnocigs\Models\Current\Article;
use MxcDropshipInnocigs\Models\Current\ArticleRepository;
use MxcDropshipInnocigs\Models\Current\Option;
use MxcDropshipInnocigs\Models\Current\OptionRepository;
use MxcDropshipInnocigs\Models\Current\Variant;
use MxcDropshipInnocigs\Models\Current\VariantRepository;
use MxcDropshipInnocigs\Models\Import\Model;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;

class ImportMapper
{
    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var PropertyMapper $propertyMapper */
    protected $propertyMapper;

    /** @var ArticleRepository $articleRepository */
    protected $articleRepository;

    /** @var VariantRepository $variantRepository */
    protected $variantRepository;

    /** @var OptionRepository $optionRepository */
    protected $optionRepository;

    /** @var array $variants */
    protected $variants;

    /** @var array */
    protected $articles;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var Config $config */
    protected $config;

    /** @var ImportModifier $importModifier */
    protected $importModifier;

    protected $optionNameMapping = [
        'blau-prisma' => 'prisma-blau',
        'chrom-prisma' => 'chrome-prisma',
        'gold-prisma' => 'prisma-gold',
        '10 mg/ml' => '- 10mg/ml',
        'grau-weiß' => 'grau-weiss',
        '0,25 Ohm' => '0,25',
        '1000er Packung' => '1000er Packubng',
        'resin-rot' => ' Resin rot',
        '0 mg/ml'   => '0 mg/mgl',
        'weiss' => ' weiß',
        '1,5 mg/ml' => '1,5 ml',
    ];

    /**
     * ImportMapper constructor.
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     * @param PropertyMapper $propertyMapper
     * @param ImportModifier $importModifier
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        PropertyMapper $propertyMapper,
        ImportModifier $importModifier,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->apiClient = $apiClient;
        $this->propertyMapper = $propertyMapper;
        $this->importModifier = $importModifier;
        $this->config = $config;
        $this->log = $log;
    }

    public function addArticleDetail(Article $article)
    {
        $variant = $article->getVariants()[0];
        $raw = $this->apiClient->getItemInfo($variant->getCode());
        $description = $this->getStringParam($raw['PRODUCTS']['PRODUCT']['DESCRIPTION']);
        if ($description === '') {
            $this->log->warn(sprintf('%s: No description available from InnoCigs for article %s.',
                __FUNCTION__,
                $article->getNumber()
            ));
            return;
        }
        if ($article->getDescription() !== $description) {
            $this->log->info(sprintf('%s: Adding article description from InnoCigs to article %s.',
                __FUNCTION__,
                $article->getNumber()
            ));
            $article->setDescription($description);
            $this->modelManager->persist($article);
        } else {
            $this->log->info(sprintf('%s: ImportArticle description from InnoCigs for article %s is up to date.',
                __FUNCTION__,
                $article->getNumber()
            ));
        }
    }

    public function getStock(Variant $variant)
    {
        $raw = $this->apiClient->getStockInfo($variant->getNumber());
        $this->log->debug(var_export($raw, true));
        return $raw['QUANTITIES']['PRODUCT']['QUANTITY'];
    }

    private function getStringParam($value)
    {
        if (is_string($value)) {
            return trim($value);
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

    public function mapOptions(string $options) {
        $options = explode('##!##', $options);
        foreach ($options as $option) {
            $option = explode('#!#', $option);
            $option[0] = $this->propertyMapper->mapGroupName($option[0]);
            $options[1] = $this->propertyMapper->mapOptionName($option[1]);
            $result[] = implode('#!#', $option);
        }
        return implode('##!##', $result);
    }

    protected function removeOptionsFromArticleName(string $name, string $options) {

        // Innocigs variant names include variant descriptions
        // We take the first variant's name and remove the variant descriptions
        // in order to extract the real article name
        $options = explode($options, '##!##');

        foreach ($options as $option) {
            $option = trim(explode($option, '#!#')[1]);
            if ($option === '1er Packung') continue;

            if (strpos($name, $option) !== false) {
                $name = str_replace($option, '', $name);
            } else {
                // They introduced some cases where the option name is not equal
                // to the string added to the article name, so we have to check
                // that, also. The implementation here is a hack right now.
                $o = $this->optionNameMapping[$option] ?? null;
                if ($o) {
                    $this->log->warn(sprintf(
                        'ImportArticle name \'%s\' does not contain the option name \'%s\'. ImportOption name mapping fix applied.',
                        $name,
                        $option
                    ));
                    $name = str_replace($o, '', $name);
                } else {
                    $this->log->warn(sprintf(
                        'ImportArticle name \'%s\' does not contain the option name \'%s\' and there is no option name mapping specified.',
                        $name,
                        $option
                    ));
                }
            }
        }
        $name = trim($name);
        if (substr($name, -2) === ' -') {
            $name = substr($name, 0, strlen($name) - 2);
        }
        return trim($name);
    }

    protected function getArticle(string $number) {
        if ($this->articles[$number]) return $this->articles[$number];
        $article = $this->articleRepository->findOneBy(['number' => $number]);
        if ($article) {
            $this->articles[$number] = $article;
            return $article;
        }
        return null;
    }

    protected function addArticle(Model $model) {
        $article = new Article();
        $this->modelManager->persist($article);
        $article->setActive(false);
        $article->setAccepted(true);

        $number = $model->getMaster();
        $this->articles[$number] = $article;
        $article->setNumber($this->propertyMapper->mapArticleNumber($number));
        $article->setIcNumber($number);

        $article->setNumber($this->propertyMapper->mapArticleNumber($number));
        $article->setIcNumber($number);
        $article->setDescription('n/a');
        $article->setManualUrl($model->getManualUrl());
        $manufacturer = $model->getManufacturer();
        $article->setManufacturer($manufacturer);
        $article->setImageUrl($model->getImageUrl());
        $name = $this->removeOptionsFromArticleName($model->getName(), $model->getOptions());
        $bs = $this->propertyMapper->mapManufacturer($number, $manufacturer);
        $article->setBrand($bs['brand']);
        $article->setSupplier($bs['supplier']);
        $article->setName($this->propertyMapper->mapArticleName($name, $number, $article));

        // this has to be last because it depends on the article properties
        $article->setCategory($this->propertyMapper->mapCategory($model->getCategory(), $number, $article));
        return $article;
    }

    protected function addVariants(array $additions) {
        /** @var  Model $model */
        foreach ($additions as $number => $model) {
            $article = $this->getArticle($model->getMaster());
            if (null === $article) {
                $article = $this->addArticle($model);
            }
            $variant = new Variant();
            $this->modelManager->persist($variant);
            $article->addVariant($variant);

            $variant->setActive(false);
            $variant->setAccepted(true);
            $variant->setNumber($this->propertyMapper->mapVariantNumber($model->getModel()));
            $variant->setEan($model->getEan());

            $price = floatval(str_replace(',', '.', $model->getPurchasePrice()));
            $variant->setPurchasePrice($price);
            $price = floatVal(str_replace(',', '.', $model->getRetailPrice()));
            $variant->setRetailPrice($price);
            $variant->setImages($model->getAdditionalImages());
            $variant->setOptions($this->mapOptions($model->getOptions()));
        }
    }

    protected function deleteVariants(array $deletions) {
        /** @var  Model $model */
        foreach ($deletions as $model) {
            /** @var  Variant $variant */
            $variant = $this->variantRepository->findOneBy([ 'model' => $model->getModel()]);
            $article = $variant->getArticle();
            $article->removeVariant($variant);
            $this->modelManager->remove($variant);
            if ($article->getVariants()->count() === 0) {
                $this->modelManager->remove($article);
            }
        }
    }

    public function changeVariants(array $changes)
    {
    }

    public function import(array $import)
    {
        $this->log->enter();
        $this->articleRepository = $this->modelManager->getRepository(Article::class);
        $this->variantRepository = $this->modelManager->getRepository(Variant::class);
        $this->optionRepository = $this->modelManager->getRepository(Option::class);
        $this->variants = $this->variantRepository->getAllIndexed();
        $this->options = $this->optionRepository->getAllIndexed();

        $this->addVariants($import['additions']);
        $this->deleteVariants($import['deletions']);
        $this->changeVariants($import['changes']);
        /** @noinspection PhpUndefinedFieldInspection */
        if ($this->config->applyFilters) {
            $this->log->notice('Applying import modifications.');
            $this->importModifier->apply();
        }
        $this->modelManager->flush();
        $this->log->leave();
        return true;
    }

    protected function getArticleRepository() {
        if (! $this->articleRepository) {
            $this->articleRepository = $this->modelManager->getRepository(Article::class);
        }
    }

    protected function getVariantRepository() {
        if (! $this->variantRepository) {
            $this->variantRepository = $this->modelManager->getRepository(Variant::class);
        }
    }
}
