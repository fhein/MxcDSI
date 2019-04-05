<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\Import\Flavorist;
use MxcDropshipInnocigs\Mapping\PropertyMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Image;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ImportMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var PropertyMapper $propertyMapper */
    protected $propertyMapper;

    /** @var ArticleMapper $articleMapper */
    protected $articleMapper;

    /** @var BulkOperation $bulkOperation */
    protected $bulkOperation;

    /** @var array $config */
    protected $config;

    /** @var array $variants */
    protected $variants;

    /** @var array $options */
    protected $options;

    /** @var array $groups */
    protected $groups;

    /** @var array */
    protected $articles;

    /** @var array */
    protected $updates;

    /** @var array */
    protected $images;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $fields;

    /**
     * ImportMapper constructor.
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     * @param PropertyMapper $propertyMapper
     * @param ArticleMapper $articleMapper
     * @param BulkOperation $bulkOperation
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        PropertyMapper $propertyMapper,
        ArticleMapper $articleMapper,
        BulkOperation $bulkOperation,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->apiClient = $apiClient;
        $this->propertyMapper = $propertyMapper;
        $this->articleMapper = $articleMapper;
        $this->bulkOperation = $bulkOperation;
        $this->config = $config->toArray();
        $this->log = $log;
    }

    protected function getGroup(string $groupName)
    {
        $group = $this->groups[$groupName];
        if (null === $group) {
            $group = new Group();
            $this->modelManager->persist($group);

            $group->setAccepted(true);
            $group->setName($groupName);
            $this->groups[$groupName] = $group;
        }
        return $group;
    }

    public function getOptions(string $optionString): ArrayCollection
    {
        $optionArray = explode(MXC_DELIMITER_L2, $optionString);
        $options = [];
        foreach ($optionArray as $option) {
            $param = explode(MXC_DELIMITER_L1, $option);
            $optionName = $this->propertyMapper->mapOptionName($param[1]);
            $groupName = $this->propertyMapper->mapGroupName($param[0]);
            $option = $this->options[$groupName][$optionName];
            if ($option === null) {
                $group = $this->getGroup($groupName);
                $option = new Option();
                $option->setAccepted(true);
                $option->setName($optionName);
                $group->addOption($option);
                $this->options[$groupName][$optionName] = $option;
            }
            $options[] = $option;
        }
        return new ArrayCollection($options);
    }

    protected function addArticle(Model $model)
    {
        $article = new Article();
        $this->modelManager->persist($article);
        $article->setIcNumber($model->getMaster());
        $article->setActive(false);
        $article->setAccepted(true);
        $article->setManual($model->getManual());
        $article->setDescription($model->getDescription());
        $article->setManufacturer($model->getManufacturer());

        return $article;
    }

    protected function getArticle(Model $model)
    {
        $number = $model->getMaster();

        // return cached article if available
        $article = $this->articles[$number];
        if ($article) {
            return $article;
        }

        $article = $this->addArticle($model);
        $this->articles[$number] = $article;

        return $article;
    }

    public function getImages(?string $imageString)
    {
        $imageUrls = explode(MXC_DELIMITER_L1, $imageString);
        $images = [];
        foreach ($imageUrls as $imageUrl) {
            $image = $this->images[$imageUrl];
            if (null === $image) {
                $image = new Image();
                $this->modelManager->persist($image);
                $image->setAccepted(true);
                $image->setUrl($imageUrl);
                $this->images[$imageUrl] = $image;
            }
            $images[] = $image;
        }
        return new ArrayCollection($images);
    }

    protected function addVariants(array $additions)
    {
        /** @var  Model $model */
        foreach ($additions as $number => $model) {
            $this->log->debug('Adding variant: ' . $model->getName());
            $article = $this->getArticle($model);

            $variant = new Variant();
            $this->modelManager->persist($variant);
            $this->variants[$model->getModel()] = $variant;
            $article->addVariant($variant);
            $variant->setActive(false);
            $variant->setAccepted(true);

            // set properties which do not require mapping
            $variant->setIcNumber($number);
            $variant->setEan($model->getEan());
            $price = $model->getPurchasePrice();
            $variant->setPurchasePrice($price);
            $uvp = $model->getRecommendedRetailPrice();
            $variant->setRecommendedRetailPrice($uvp);
            $variant->setRetailPrices('EK' . MXC_DELIMITER_L1 . $uvp);

            $images = $model->getImages();
            if (null !== $images) {
                $variant->setImages($this->getImages($images));
            }
            $variant->setOptions($this->getOptions($model->getOptions()));
        }
    }

    protected function deleteVariants(array $deletions)
    {
        /** @var  Model $model */
        $variantRepository = $this->modelManager->getRepository(Variant::class);
        foreach ($deletions as $model) {
            /** @var  Variant $variant */
            $variant = $variantRepository->findOneBy(['number' => $model->getModel()]);
            $variant->removeImagesAndOptions();
            $article = $variant->getArticle();
            $article->removeVariant($variant);
            $this->modelManager->remove($variant);
            $icNumber = $variant->getIcNumber();
            unset($this->variants[$icNumber]);
            if ($article->getVariants()->count() === 0) {
                $article->setAccepted(false);
                unset($this->articles[$article->getIcNumber()]);
            }
            $this->updates[$article->getIcNumber()] = $article;
        }
    }

    protected function changeOptions(Variant $variant, string $oldValue, string $newValue)
    {
        $oldOptions = explode(MXC_DELIMITER_L2, $oldValue);
        $newOptions = explode(MXC_DELIMITER_L2, $newValue);
        $rOptions = array_diff($oldOptions, $newOptions);
        foreach ($rOptions as $option) {
            $param = explode(MXC_DELIMITER_L1, $option);
            $variant->removeOption($this->options[$param[0]][$param[1]]);
        }
        $addedOptions = array_diff($newOptions, $oldOptions);
        $addedOptions = implode(MXC_DELIMITER_L2, $addedOptions);
        $addedOptions = $this->getOptions($addedOptions);
        $variant->addOptions($this->getOptions($addedOptions));
    }

    protected function changeImages(Variant $variant, string $oldValue, string $newValue)
    {
        $oldImages = explode(MXC_DELIMITER_L1, $oldValue);
        $newImages = explode(MXC_DELIMITER_L1, $newValue);

        $removed = array_diff($oldImages, $newImages);
        foreach ($removed as $url) {
            $variant->removeImage($this->images[$url]);
        }

        $addedImages = implode(MXC_DELIMITER_L1, array_diff($newImages, $oldImages));
        $addedImages = $this->getImages($addedImages);
        $variant->addImages($addedImages);
    }

    protected function changeVariant(Variant $variant, Model $model, array $fields)
    {
        $this->log->debug('Changing variant: ' . $model->getName());
        foreach ($fields as $name => $values) {
            $newValue = $values['newValue'];
            $oldValue = $values['oldValue'];
            switch ($name) {
                case 'category':
                    $this->propertyMapper->mapArticleCategory($model, $variant->getArticle());
                    break;
                case 'ean':
                    $variant->setEan($newValue);
                    break;
                case 'name':
                    $this->propertyMapper->mapArticleName($model, $variant->getArticle());
                    break;
                case 'purchasePrice':
                    $variant->setPurchasePrice($newValue);
                    break;
                case 'retailPrice':
                    $variant->setRecommendedRetailPrice($newValue);
                    break;
                case 'manufacturer':
                    $this->propertyMapper->mapArticleManufacturer($model, $variant->getArticle());
                    break;
                case 'images':
                    $this->changeImages($variant, $oldValue, $newValue);
                    break;
                case 'options':
                    $this->changeOptions($variant, $oldValue, $newValue);
                    break;
                case 'master':
                    $variant->getArticle()->removeVariant($variant);
                    $this->getArticle($newValue)->addVariant($variant);
                    break;
            }
        }
    }

    protected function changeVariants(array $changes)
    {
        foreach ($changes as $icNumber => $change) {
            /** @var Variant $variant */
            $variant = $this->variants[$icNumber];
            $model = $change['model'];
            $fields = $change['fields'];
            $this->changeVariant($variant, $model, $fields);
            $article = $variant->getArticle();
            $this->updates[$article->getIcNumber()] = $article;
        }
    }

    protected function removeOrphanedItems()
    {
        $this->modelManager->getRepository(Article::class)->removeOrphaned();
        $this->modelManager->getRepository(Variant::class)->removeOrphaned();

        // Orphaned options must be removed before orphaned groups because groups may
        // become orphaned during removal of orphaned options
        $this->modelManager->getRepository(Option::class)->removeOrphaned();
        $this->modelManager->getRepository(Group::class)->removeOrphaned();

        $this->modelManager->getRepository(Image::class)->removeOrphaned();
    }


    protected function initCache()
    {
        $this->modelManager->createQuery('UPDATE ' . Article::class . ' a set a.new = false')->execute();
        $this->modelManager->createQuery('UPDATE ' . Variant::class . ' a set a.new = false')->execute();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->variants = $this->modelManager->getRepository(Variant::class)->getAllIndexed();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->groups = $this->modelManager->getRepository(Group::class)->getAllIndexed();
        $this->options = $this->modelManager->getRepository(Option::class)->getAllIndexed();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->images = $this->modelManager->getRepository(Image::class)->getAllIndexed();
    }

    protected function attachLinkedShopwareArticles()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->modelManager->getRepository(Article::class)->linkArticles();
    }

    public function import(array $import)
    {
        $this->updates = [];
        $this->initCache();

        $this->addVariants($import['additions']);
        $this->deleteVariants($import['deletions']);
        $this->changeVariants($import['changes']);
        $this->propertyMapper->mapProperties($this->articles);
        $this->modelManager->flush();

        $this->modelManager->flush();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->modelManager->getRepository(Article::class)->linkArticles();

        $this->articleMapper->updateShopwareArticles($this->updates);

        if ($this->config['applyFilters']) {
            foreach ($this->config['filters']['update'] as $filter) {
                $this->bulkOperation->update($filter);
            }
        }

        $flavorist = new Flavorist($this->modelManager, $this->log);
        $flavorist->updateCategories();
        $flavorist->updateFlavors();

        return true;
    }
}
