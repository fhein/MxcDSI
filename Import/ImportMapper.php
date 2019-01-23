<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Exception\InvalidArgumentException;
use MxcDropshipInnocigs\Models\Current\Article;
use MxcDropshipInnocigs\Models\Current\Group;
use MxcDropshipInnocigs\Models\Current\Image;
use MxcDropshipInnocigs\Models\Current\Option;
use MxcDropshipInnocigs\Models\Current\Variant;
use MxcDropshipInnocigs\Models\Import\ImportArticle;
use MxcDropshipInnocigs\Models\Import\ImportGroup;
use MxcDropshipInnocigs\Models\Import\ImportImage;
use MxcDropshipInnocigs\Models\Import\ImportOption;
use MxcDropshipInnocigs\Models\Import\ImportVariant;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;

class ImportMapper
{
    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var ImportClient $importClient */
    protected $importClient;

    /** @var PropertyMapper $propertyMapper */
    protected $propertyMapper;

    /** @var array $options */
    protected $options;

    /** @var array $images */
    protected $images;

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
     * @param ImportClient $importClient ,
     *
     * @param PropertyMapper $propertyMapper
     * @param ImportModifier $importModifier
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        ImportClient $importClient,
        PropertyMapper $propertyMapper,
        ImportModifier $importModifier,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->apiClient = $apiClient;
        $this->importClient = $importClient;
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
            $this->log->info(sprintf('%s: ImportArticle description from InnoCigs for article %s is up to date.',
                __FUNCTION__,
                $article->getCode()
            ));
        }
    }

    public function getStock(Variant $variant)
    {
        $raw = $this->apiClient->getStockInfo($variant->getCode());
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

    protected function createGroups(array $importGroups)
    {
        /** @var ImportGroup $importGroup */
        foreach ($importGroups as $importGroup) {
            $group = new Group();
            $this->modelManager->persist($group);

            $group->setName($this->propertyMapper->mapGroupName($importGroup->getName()));
            $group->setAccepted(true);
            $this->createOptions($group, $importGroup);
        }
    }

    protected function createOptions(Group $group, ImportGroup $importGroup)
    {
        /** @var ImportOption $importOption */
        $importOptions = $importGroup->getOptions();
        foreach ($importOptions as $importOption) {
            $option = new Option();

            $optionName = $importOption->getName();
            $option->setName($this->propertyMapper->mapOptionName($optionName));

            $option->setAccepted(true);
            $group->addOption($option);

            $this->options[$importGroup->getName()][$optionName] = $option;
        }
    }

    protected function createArticles($importArticles, int $limit = -1)
    {
        $i = 0;
        /** @var ImportArticle $importArticle */
        foreach ($importArticles as $importArticle) {
            $article = new Article();
            // this cascades persisting the variants also
            $this->modelManager->persist($article);

            $article->setActive(false);
            $article->setAccepted(true);

            $this->createVariants($article, $importArticle->getVariants());
            $number = $importArticle->getNumber();
            $article->setCode($this->propertyMapper->mapArticleCode($number));
            $article->setIcCode($number);
            $article->setDescription('n/a');
            /** @var ImportVariant $v0 */
            $v0 = $importArticle->getVariants()[0];
            $article->setManualUrl($v0->getManualUrl());
            $article->setManufacturer($v0->getManufacturer());
            $name = $this->removeOptionsFromArticleName($v0->getName(), $v0->getOptions());
            $image = $v0->getImage();

            $bs = $this->propertyMapper->mapManufacturer($number, $v0->getManufacturer());
            $article->setBrand($bs['brand']);
            $article->setSupplier($bs['supplier']);
            $article->setName($this->propertyMapper->mapArticleName($name, $number, $article));

            if (null !== $image) {
                $article->setImageUrl($image->getUrl());
            }

            // this has to be last because it depends on the article properties
            $article->setCategory($this->propertyMapper->mapCategory($v0->getCategory(), $number, $article));

            $i++;
            if ($limit !== -1 && $i === $limit) {
                break;
            }
        }
    }

    protected function getImage(ImportImage $image) {
        $url = $image->getUrl();
        $image = $this->images[$url];
        if ($image instanceof Image) {
            return $image;
        }
        $image = new Image;
        $image->setUrl($url);
        $this->images[$url] = $image;
        return $image;
    }

    protected function removeOptionsFromArticleName(string $name, Collection $importOptions) {
        // Innocigs variant names include variant descriptions
        // We take the first variant's name and remove the variant descriptions
        // in order to extract the real article name
        foreach ($importOptions as $importOption) {
            $option = trim($importOption->getName());
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

    protected function createVariants(Article $article, Collection $importVariants)
    {
        // mark all variants of active articles active
        $active = $article->isActive();
        $accepted = $article->isAccepted();
        /** @var ImportVariant $importVariant */
        foreach ($importVariants as $importVariant) {
            $variant = new Variant();
            // This persist is necessary allthough the relation is defined cascade persist.
            // I assume this is because Option holds a one to many
            $this->modelManager->persist($variant);

            $article->addVariant($variant);

            $variant->setActive($active);
            $variant->setAccepted($accepted);

            $variant->setCode($this->propertyMapper->mapVariantCode($importVariant->getNumber()));
            $variant->setEan($importVariant->getEan());

            $price = floatval(str_replace(',', '.', $importVariant->getPurchasePrice()));
            $variant->setPurchasePrice($price);

            $price = floatVal(str_replace(',', '.', $importVariant->getRetailPrice()));
            $variant->setRetailPrice($price);
            /** @var ImportImage $importImage */
            foreach ($importVariant->getAdditionalImages() as $importImage) {
                $image = $this->getImage($importImage);
                $variant->addImage($image);
            }
            /** @var ImportOption $importOption */
            foreach ($importVariant->getOptions() as $importOption) {
                $group = $importOption->getIcGroup()->getName();
                $option = $this->options[$group][$importOption->getName()];
                $variant->addOption($option);
            }
        }
    }

    public function import()
    {
        $this->log->enter();
        // only import articles if we do not have them
        $importArticleRepository = $this->modelManager->getRepository(ImportArticle::class);
        $articleRepository = $this->modelManager->getRepository(Article::class);
        if ($articleRepository->count() === 0) {
            if ($importArticleRepository->count() === 0) {
                $this->importClient->import();
            }

            $groups = $this->modelManager->getRepository(ImportGroup::class)->findAll();
            $this->createGroups($groups);

            $importArticles = $importArticleRepository->findAll();
            /** @noinspection PhpUndefinedFieldInspection */
            $limit  = $this->config->numberOfArticles ?? -1;
            $this->createArticles($importArticles, $limit);

            /** @noinspection PhpUndefinedFieldInspection */
            if ($this->config->applyFilters) {
                $this->log->notice('Applying import modifications.');
                $this->importModifier->apply();
            }
        }
        $this->modelManager->flush();
        $this->log->leave();
        return true;
    }
}
