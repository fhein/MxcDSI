<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\OptimisticLockException;
use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Mapping\Import\Flavorist;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\GroupRepository;
use MxcDropshipInnocigs\Models\Image;
use MxcDropshipInnocigs\Models\ImageRepository;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\OptionRepository;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Models\VariantRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ImportMapper implements ModelManagerAwareInterface, LoggerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;

    /** @var Flavorist */
    protected $flavorist;

    /** @var VariantRepository */
    protected $variantRepository;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var GroupRepository */
    protected $groupRepository;

    /** @var OptionRepository */
    protected $optionRepository;

    /** @var ImageRepository */
    protected $imageRepository;

    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var DetailMapper */
    protected $detailMapper;

    /** @var PropertyMapper $propertyMapper */
    protected $propertyMapper;

    /** @var ProductMapper $shopwareMapper */
    protected $shopwareMapper;

    /** @var ArticleTool */
    protected $articleTool;

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
    protected $products;

    /** @var array */
    protected $updates;

    /** @var array */
    protected $images;

    /**
     * ImportMapper constructor.
     *
     * @param ArticleTool $articleTool
     * @param ApiClient $apiClient
     * @param PropertyMapper $propertyMapper
     * @param ProductMapper $shopwareMapper
     * @param DetailMapper $detailMapper
     * @param BulkOperation $bulkOperation
     * @param Flavorist $flavorist
     */
    public function __construct(
        ArticleTool $articleTool,
        ApiClient $apiClient,
        PropertyMapper $propertyMapper,
        ProductMapper $shopwareMapper,
        DetailMapper $detailMapper,
        BulkOperation $bulkOperation,
        Flavorist $flavorist
    ) {
        $this->articleTool = $articleTool;
        $this->detailMapper = $detailMapper;
        $this->apiClient = $apiClient;
        $this->propertyMapper = $propertyMapper;
        $this->shopwareMapper = $shopwareMapper;
        $this->bulkOperation = $bulkOperation;
        $this->flavorist = $flavorist;
    }

    protected function mapGroup(string $groupName)
    {
        $group = @$this->groups[$groupName];
        if (null === $group) {
            $group = new Group();
            $this->modelManager->persist($group);

            $group->setAccepted(true);
            $group->setName($groupName);
            $this->groups[$groupName] = $group;
        }
        return $group;
    }

    public function mapOptions(string $optionString): ArrayCollection
    {
        $optionArray = explode(MXC_DELIMITER_L2, $optionString);
        $options = [];
        foreach ($optionArray as $option) {
            $param = explode(MXC_DELIMITER_L1, $option);
            $optionName = $this->propertyMapper->mapOptionName($param[1]);
            $groupName = $this->propertyMapper->mapGroupName($param[0]);
            $option = @$this->options[$groupName][$optionName];
            if ($option === null) {
                $group = $this->mapGroup($groupName);
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

    protected function addProduct(Model $model)
    {
        $product = new Product();
        $this->modelManager->persist($product);
        $product->setIcNumber($model->getMaster());
        $product->setActive(false);
        $product->setAccepted(true);
        $product->setManual($model->getManual());
        $product->setDescription($model->getDescription());
        $product->setManufacturer($model->getManufacturer());

        return $product;
    }

    protected function mapProduct(Model $model)
    {
        $number = $model->getMaster();

        // return cached product if available
        $product = @$this->products[$number];
        if ($product) return $product;

        $product = $this->addProduct($model);
        $this->products[$number] = $product;

        return $product;
    }

    public function mapImages(?string $imageString)
    {
        $imageUrls = explode(MXC_DELIMITER_L1, $imageString);
        $images = [];
        foreach ($imageUrls as $imageUrl) {
            $image = @$this->images[$imageUrl];
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
            $product = $this->mapProduct($model);
            $this->updates[$product->getIcNumber()] = $product;

            $variant = new Variant();
            $this->modelManager->persist($variant);
            $this->variants[$model->getModel()] = $variant;
            $product->addVariant($variant);
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
                $variant->setImages($this->mapImages($images));
            }
            $variant->setOptions($this->mapOptions($model->getOptions()));
        }
    }

    /**
     * Find the Variants associated with list of deleted Models, set their
     * accepted state to false. Return a list of all Products owning one
     * of the modified Variants.
     *
     * @param array $deletions
     * @return array
     * @throws OptimisticLockException
     */
    protected function invalidateVariants(array $deletions): array
    {
        /** @var  Model $model */
        $variantRepository = $this->getVariantRepository();

        $productsWithDeletions = [];
        foreach ($deletions as $model) {
            /** @var  Variant $variant */
            $variant = $variantRepository->findOneBy(['number' => $model->getModel()]);
            $variant->setAccepted(false);
            $product = $variant->getProduct();
            $productsWithDeletions[$product->getIcNumber()] = $product;
        }

        $this->modelManager->flush();
        return $productsWithDeletions;
    }

    /**
     * Remove all invalid Variants together with the Options and Images
     * belonging to them. Remove the Product also, if it has no variant
     * left.
     *
     * @param array $products
     * @throws OptimisticLockException
     */
    protected function removeInvalidVariants(array $products)
    {
        $variantRepository = $this->getVariantRepository();

        foreach ($products as $product) {
            $invalidVariants = $this->getProductRepository()->getInvalidVariants($product);
            /** @var Variant $variant */
            foreach ($invalidVariants as $variant) {
                $variantRepository->removeImages($variant);
                $variantRepository->removeOptions($variant);
                $product->removeVariant($variant);
                $this->modelManager->remove($variant);
                unset($this->variants[$variant->getIcNumber()]);
                if ($product->getVariants()->count === 0) {
                    $this->modelManager->remove($product);
                    unset ($this->products[$product->getIcNumber()]);
                }
            }
        }

        $this->modelManager->flush();

    }

    protected function deleteVariants(array $deletions)
    {
        if (empty($deletions)) return;

        $productsWithDeletions = $this->invalidateVariants($deletions);
        $this->detailMapper->deleteInvalidDetails($productsWithDeletions);
        $this->removeInvalidVariants($productsWithDeletions);
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
        $addedOptions = $this->mapOptions($addedOptions);
        $variant->addOptions($this->mapOptions($addedOptions));
    }

    protected function changeImages(Variant $variant, string $oldValue, string $newValue)
    {
        $oldImages = explode(MXC_DELIMITER_L1, $oldValue);
        $newImages = explode(MXC_DELIMITER_L1, $newValue);

        $removed = array_diff($oldImages, $newImages);
        foreach ($removed as $url) {
            $image = $this->images[$url];
            $variant->removeImage($image);
        }

        $addedImages = array_diff($newImages, $oldImages);
        if (! empty($addedImages)) {
            $addedImages = implode(MXC_DELIMITER_L1, $addedImages);
            $this->log->debug('Added images' . var_export($addedImages, true));
            $addedImages = $this->mapImages($addedImages);
            $variant->addImages($addedImages);
        }
    }

    protected function changeVariant(Variant $variant, Model $model, array $fields)
    {
        $this->log->debug('Changing variant: ' . $model->getName());
        foreach ($fields as $name => $values) {
            $newValue = $values['newValue'];
            $oldValue = $values['oldValue'];
            switch ($name) {
                case 'category':
                    $this->propertyMapper->mapProductCategory($model, $variant->getProduct());
                    break;
                case 'ean':
                    $variant->setEan($newValue);
                    break;
                case 'name':
                    $this->propertyMapper->mapProductName($model, $variant->getProduct());
                    break;
                case 'purchasePrice':
                    $variant->setPurchasePrice($newValue);
                    break;
                case 'retailPrice':
                    $variant->setRecommendedRetailPrice($newValue);
                    break;
                case 'manufacturer':
                    $this->propertyMapper->mapProductManufacturer($model, $variant->getProduct());
                    break;
                case 'images':
                    $this->changeImages($variant, $oldValue, $newValue);
                    break;
                case 'options':
                    $this->changeOptions($variant, $oldValue, $newValue);
                    break;
                case 'master':
                    $variant->getProduct()->removeVariant($variant);
                    $this->mapProduct($newValue)->addVariant($variant);
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
            $product = $variant->getProduct();
            $this->updates[$product->getIcNumber()] = $product;
        }
    }

    protected function removeOrphanedItems()
    {
        $this->getVariantRepository()->removeOrphaned();
        $this->getProductRepository()->removeOrphaned();

        $this->getOptionRepository()->removeOrphaned();
        $this->getImageRepository()->removeOrphaned();

        // Orphaned options must be removed before orphaned groups. Groups may become
        // orphaned during removal of orphaned options
        $this->getGroupRepository()->removeOrphaned();
        $this->modelManager->flush();

    }


    protected function initCache()
    {
        $this->modelManager->createQuery('UPDATE ' . Product::class . ' a set a.new = false')->execute();
        $this->modelManager->createQuery('UPDATE ' . Variant::class . ' a set a.new = false')->execute();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->products = $this->getProductRepository()->getAllIndexed();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->variants = $this->getVariantRepository()->getAllIndexed();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->groups = $this->getGroupRepository()->getAllIndexed();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->images = $this->getImageRepository()->getAllIndexed();

        $this->options = $this->getOptionRepository()->getAllIndexed();
    }

    public function import(array $import)
    {
        $this->updates = [];
        $this->initCache();

        $this->addVariants($import['additions']);
        $this->changeVariants($import['changes']);
        $this->propertyMapper->mapProperties($this->products);

        $this->modelManager->flush();

        $this->deleteVariants($import['deletions']);

        //$this->modelManager->clear();
        $this->removeOrphanedItems();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->modelManager->getRepository(Product::class)->refreshLinks();

        $this->shopwareMapper->updateArticles($this->updates);

        if (@$this->config['applyFilters']) {
            foreach ($this->config['filters']['update'] as $filter) {
                $this->bulkOperation->update($filter);
            }
        }

        $this->flavorist->updateCategories();
        $this->flavorist->updateFlavors();

        return true;
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->productRepository ?? $this->productRepository = $this->modelManager->getRepository(Product::class);
    }

    protected function getProducts() {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products ?? $this->products = $this->getProductRepository()->getAllIndexed();
    }

    /**
     * @return VariantRepository
     */
    protected function getVariantRepository()
    {
        return $this->variantRepository ?? $this->variantRepository = $this->modelManager->getRepository(Variant::class);
    }

    protected function getVariants() {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->variants ?? $this->variants = $this->getVariantRepository()->getAllIndexed();
    }

    /**
     * @return GroupRepository
     */
    protected function getGroupRepository()
    {
        return $this->groupRepository ?? $this->groupRepository = $this->modelManager->getRepository(Group::class);
    }

    protected function getGroups() {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->groups ?? $this->groups = $this->getGroupRepository()->getAllIndexed();
    }

    /**
     * @return OptionRepository
     */
    protected function getOptionRepository()
    {
        return $this->optionRepository ?? $this->optionRepository = $this->modelManager->getRepository(Option::class);
    }

    protected function getOptions()
    {
        return $this->options ?? $this->options = $this->getOptionRepository()->getAllIndexed();
    }

    /**
     * @return ImageRepository
     */
    protected function getImageRepository()
    {
        return $this->imageRepository ?? $this->imageRepository = $this->modelManager->getRepository(Image::class);
    }

    protected function getImages()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->images ?? $this->images = $this->getImageRepository()->getAllIndexed();
    }

}
