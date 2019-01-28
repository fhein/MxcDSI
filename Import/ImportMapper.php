<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Exception\InvalidArgumentException;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\ArticleRepository;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Image;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\OptionRepository;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Models\VariantRepository;
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

    /** @var array $options */
    protected $options;

    /** @var array $groups */
    protected $groups;

    /** @var array */
    protected $articles;

    /** @var array */
    protected $images;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var Config $config */
    protected $config;

    /** @var ImportModifier $importModifier */
    protected $importModifier;

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
        $raw = $this->apiClient->getItemInfo($variant->getNumber());
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
            $this->log->info(sprintf('%s: Article description from InnoCigs for article %s is up to date.',
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

    protected function getGroup(string $groupName) {
        $group = $this->groups[$groupName];
        if (null === $group) {
            $group = new Group();
            $group->setAccepted(true);
            $group->setName($groupName);
            $this->modelManager->persist($group);
            $this->groups[$groupName] = $group;
        }
        return $group;
    }

    public function getOptions(string $optionString): ArrayCollection
    {
        $optionArray = explode('##!##', $optionString);
        $options = [];
        foreach ($optionArray as $option) {
            $param = explode('#!#', $option);
            $optionName = $this->propertyMapper->mapOptionName($param[1]);
            $groupName = $this->propertyMapper->mapGroupName($param[0]);
            $option = $this->options[$groupName][$optionName];
            if ($option === null) {
                $group = $this->getGroup($groupName);
                $option = new Option();
                $option->setAccepted(true);
                $option->setName($optionName);
                $group->addOption($option);
                $this->modelManager->persist($group);
                $this->options[$groupName][$optionName] = $option;
            }
            $options[] = $option;
        }
        return new ArrayCollection($options);
    }

    protected function addArticle(Model $model) {
        $article = new Article();
        $this->modelManager->persist($article);
        $article->setActive(false);
        $article->setAccepted(true);
        $this->propertyMapper->modelToArticle($model, $article);
        return $article;
    }

    protected function getArticle(Model $model) {
        $number = $model->getMaster();

        // return cached article if available
        $article = $this->articles[$number];
        if ($article) return $article;

        // get from database or create new article
        $article = $this->articleRepository->findOneBy(['number' => $number]) ?? $this->addArticle($model);

        // add to cache
        $this->articles[$number] = $article;

        return $article;
    }

    public function getImages(?string $imageString) {
        $imageUrls = explode('#!#', $imageString);
        $images = [];
        foreach ($imageUrls as $imageUrl) {
            $image = $this->images[$imageUrl];
            if (null === $image) {
                $image = new Image();
                $this->modelManager->persist($image);
                $image->setAccepted(true);
                $image->setUrl($imageUrl);
                $this->images[$imageUrl] = $image;
                $images[] = $image;
            }
        }
        return new ArrayCollection($images);
    }

    protected function addVariants(array $additions) {
        /** @var  Model $model */
        foreach ($additions as $number => $model) {
            $article = $this->getArticle($model);
            $variant = new Variant();
            $this->modelManager->persist($variant);
            $this->variants[$model->getModel()] = $variant;
            $article->addVariant($variant);
            $variant->setActive(false);
            $variant->setAccepted(true);

            $this->propertyMapper->modelToVariant($model, $variant);

            $images = $model->getAdditionalImages();
            if (null !== $images) {
                $variant->setImages($this->getImages($images));
            }
            $variant->setOptions($this->getOptions($model->getOptions()));
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

    protected function changeOptions(Article $article, string $newValue) {

    }

    protected function changeImages(Variant $variant, string $newValue)
    {
    }

    protected function changeVariant(Variant $variant, Model $model, array $fields) {
        foreach ($fields as $name => $values) {
            $newValue = $values['newValue'];
            switch ($name) {
                case 'category':
                    $this->propertyMapper->mapCategory($variant->getArticle(), $newValue);
                    break;
                case 'ean':
                    $variant->setEan($newValue);
                    break;
                case 'name':
                    $name = $this->propertyMapper->removeOptionsFromArticleName($newValue, $model->getOptions());
                    $this->propertyMapper->mapArticleName($variant->getArticle(), $name);
                    break;
                case 'purchasePrice':
                    $price = floatval(str_replace(',', '.', $newValue));
                    $variant->setPurchasePrice($price);
                    break;
                case 'retailPrice':
                    $price = floatval(str_replace(',', '.', $newValue));
                    $variant->setRetailPrice($price);
                    break;
                case 'manufacturer':
                    $this->propertyMapper->mapManufacturer($variant->getArticle(), $newValue);
                    break;
                case 'imageUrl':
                    $variant->getArticle()->setImageUrl($newValue);
                    break;
                case 'additionalImages':
                    $this->changeImages($variant, $newValue);
                    break;
                case 'options':
                    $this->changeOptions($variant->getArticle(), $newValue);
                    break;
            }
        }
    }

    protected function changeVariants(array $changes)
    {
        foreach ($changes as $number => $change) {
            $variant = $this->variants[$number];
            $model = $change['model'];
            $fields = $change['fields'];
            $this->changeVariant($variant, $model, $fields);
        }
    }

    public function import(array $import)
    {
        $this->log->enter();
        $this->articleRepository = $this->modelManager->getRepository(Article::class);
        $this->variantRepository = $this->modelManager->getRepository(Variant::class);
        $this->optionRepository = $this->modelManager->getRepository(Option::class);
        $this->variants = $this->variantRepository->getAllIndexed();
        $this->options = $this->optionRepository->getAllIndexed();
        $this->groups = $this->modelManager->getRepository(Group::class)->getAllIndexed();
        $this->images = $this->modelManager->getRepository(Image::class)->getAllIndexed();

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
