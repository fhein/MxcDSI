<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
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

class ImportMapper implements EventSubscriber
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var PropertyMapper $propertyMapper */
    protected $propertyMapper;

    /** @var PropertyDerivator $propertyExtractor */
    protected $propertyExtractor;

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
    protected $images;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $importLog;

    /** @var array */
    protected $fields;

    /**
     * ImportMapper constructor.
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     * @param PropertyMapper $propertyMapper
     * @param PropertyDerivator $propertyExtractor
     * @param BulkOperation $bulkOperation
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        PropertyMapper $propertyMapper,
        PropertyDerivator $propertyExtractor,
        BulkOperation $bulkOperation,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->apiClient = $apiClient;
        $this->propertyMapper = $propertyMapper;
        $this->propertyExtractor = $propertyExtractor;
        $this->bulkOperation = $bulkOperation;
        $this->config = $config->toArray();
        $this->log = $log;
    }

    public function getStock(Variant $variant)
    {
        $raw = $this->apiClient->getStockInfo($variant->getNumber());
        return $raw['QUANTITIES']['PRODUCT']['QUANTITY'];
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
        $article->setIcNumber($model->getMaster());
        $article->setActive(false);
        $article->setAccepted(true);
        $article->setManual($model->getManual());
        $article->setDescription($model->getDescription());

        $this->propertyMapper->mapModelToArticle($model, $article);
        $this->log->info(sprintf(
            'New article: %s - %s',
            $article->getIcNumber(),
            $article->getName()
        ));
        return $article;
    }

    protected function getArticle(Model $model) {
        $number = $model->getMaster();

        // return cached article if available
        $article = $this->articles[$number];
        if ($article) return $article;

        $article = $this->addArticle($model);
        $this->articles[$number] = $article;

        return $article;
    }

    public function getImages(?string $imageString) {
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

    protected function addVariants(array $additions) {
        /** @var  Model $model */
        foreach ($additions as $number => $model) {
            $article = $this->getArticle($model);

            $flavor = $this->config['flavors'][$article->getIcNumber()]['flavor'];
            if (is_array($flavor) && ! empty($flavor)) {
                $article->setFlavor(implode(', ', $flavor));
            }
            $variant = new Variant();
            $this->modelManager->persist($variant);
            $this->variants[$model->getModel()] = $variant;
            $article->addVariant($variant);
            $variant->setActive(false);
            $variant->setAccepted(true);

            // set properties without mapping
            $variant->setIcNumber($number);
            $variant->setEan($model->getEan());
            $price = floatval(str_replace(',', '.', $model->getPurchasePrice()));
            $variant->setPurchasePrice($price);
            $price = floatVal(str_replace(',', '.', $model->getRetailPrice()));
            $variant->setRetailPrice($price);

            // set mapped properties
            $this->propertyMapper->mapModelToVariant($model, $variant);

            $images = $model->getImages();
            if (null !== $images) {
                $variant->setImages($this->getImages($images));
            }
            $variant->setOptions($this->getOptions($model->getOptions()));
        }
    }

    protected function deleteVariants(array $deletions) {
        /** @var  Model $model */
        $variantRepository = $this->modelManager->getRepository(Variant::class);
        foreach ($deletions as $model) {
            /** @var  Variant $variant */
            $variant = $variantRepository->findOneBy([ 'number' => $model->getModel()]);
            $variant->removeImagesAndOptions();
            $article = $variant->getArticle();
            $article->removeVariant($variant);
            $this->modelManager->remove($variant);
            if ($article->getVariants()->count() === 0) {
                $this->modelManager->remove($article);
            }
        }
    }

    protected function changeOptions(Variant $variant, string $oldValue, string $newValue) {
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

    protected function changeVariant(Variant $variant, Model $model, array $fields) {
        foreach ($fields as $name => $values) {
            $newValue = $values['newValue'];
            $oldValue = $values['oldValue'];
            switch ($name) {
                case 'category':
                    $this->propertyMapper->mapCategory($model, $variant->getArticle());
                    break;
                case 'ean':
                    $variant->setEan($newValue);
                    break;
                case 'name':
                    $this->propertyMapper->mapArticleName($model, $variant->getArticle());
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
        foreach ($changes as $number => $change) {
            $variant = $this->variants[$number];
            $model = $change['model'];
            $fields = $change['fields'];
            $this->changeVariant($variant, $model, $fields);
        }
    }

    protected function removeOrphanedItems() {
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
        $this->modelManager->createQuery('UPDATE '. Article::class . ' a set a.new = false')->execute();
        $this->modelManager->createQuery('UPDATE '. Variant::class . ' a set a.new = false')->execute();
        $this->articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        $this->variants = $this->modelManager->getRepository(Variant::class)->getAllIndexed();
        $this->groups = $this->modelManager->getRepository(Group::class)->getAllIndexed();
        $this->options = $this->modelManager->getRepository(Option::class)->getAllIndexed();
        $this->images = $this->modelManager->getRepository(Image::class)->getAllIndexed();
    }

    protected function initFields() {
        foreach ([Article::class, Variant::class, Group::class, Option::class, Image::class] as $class) {
            /** @var Article $o */
            $o = new $class();
            $this->fields[$class] = $o->getPrivatePropertyNames();
        }
    }

    protected function getClass($object) {
        foreach ([Article::class, Variant::class, Group::class, Option::class, Image::class] as $class) {
            if ($object instanceof $class) return $class;
        }
        return null;
    }

    public function import(array $import)
    {
        $evm = $this->modelManager->getEventManager();
        $evm->addEventSubscriber($this);
        $this->initCache();
        $this->initFields();

        $this->addVariants($import['additions']);
        $this->deleteVariants($import['deletions']);
        $this->changeVariants($import['changes']);
        $this->modelManager->flush();
        $this->modelManager->clear();
        $evm->removeEventSubscriber($this);

        if ($this->config['applyFilters']) {
            foreach($this->config['filters']['update'] as $filter) {
                $this->bulkOperation->update($filter);
            }
        }
        $this->propertyMapper->report();

        $flavorist = new Flavorist($this->modelManager, $this->log);
        $flavorist->updateCategories();
        $flavorist->updateFlavors();

        $this->propertyExtractor->derive();
        $this->propertyExtractor->export();
        return true;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        /** @var PreUpdateEventArgs $args */
        $entity = $args->getEntity();
        $class = $this->getClass($entity);
        $fields = $this->fields[$class];
        if (null === $fields) return;

        $changes['entity'] = $entity;
        foreach ($this->fields as $field) {
            if ($args->hasChangedField($field)) {
                $changes['fields'][$field] = [
                    'oldValue' => $args->getOldValue($field),
                    'newValue' => $args->getNewValue($field)
                ];
            }
        }
        $this->importLog['changes'][$class][] = $changes;
    }

    public function postPersist(LifecycleEventArgs $args) {

    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [ 'preUpdate', 'postPersist'];
    }
}
