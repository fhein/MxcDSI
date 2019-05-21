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
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\GroupRepository;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\ModelRepository;
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

    /** @var VariantRepository */
    protected $variantRepository;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var GroupRepository */
    protected $groupRepository;

    /** @var OptionRepository */
    protected $optionRepository;

    /** @var ModelRepository */
    protected $modelRepository;

    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var DetailMapper */
    protected $detailMapper;

    /** @var PropertyMapper $propertyMapper */
    protected $propertyMapper;

    /** @var ProductMapper $productMapper */
    protected $productMapper;

    /** @var CategoryMapper */
    protected $categoryMapper;

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

    /**
     * ImportMapper constructor.
     *
     * @param ArticleTool $articleTool
     * @param ApiClient $apiClient
     * @param PropertyMapper $propertyMapper
     * @param CategoryMapper $categoryMapper
     * @param ProductMapper $productMapper
     * @param DetailMapper $detailMapper
     * @param BulkOperation $bulkOperation
     */
    public function __construct(
        ArticleTool $articleTool,
        ApiClient $apiClient,
        PropertyMapper $propertyMapper,
        CategoryMapper $categoryMapper,
        ProductMapper $productMapper,
        DetailMapper $detailMapper,
        BulkOperation $bulkOperation
    ) {
        $this->articleTool = $articleTool;
        $this->detailMapper = $detailMapper;
        $this->apiClient = $apiClient;
        $this->categoryMapper = $categoryMapper;
        $this->propertyMapper = $propertyMapper;
        $this->productMapper = $productMapper;
        $this->bulkOperation = $bulkOperation;
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
        $description = $model->getDescription();
        $product->setDescription($description);
        $product->setIcDescription($description);
        $product->setManufacturer($model->getManufacturer());
        $this->propertyMapper->mapModelToProduct($model, $product);

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

    protected function addNewVariants()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $additions = $this->getModelRepository()->getModelsWithoutVariant();
        /** @var  Model $model */
        foreach ($additions as $model) {
            $this->log->debug('Adding variant: ' . $model->getName());
            $product = $this->mapProduct($model);
            $this->updates[$product->getIcNumber()] = $product;

            $variant = new Variant();
            $this->modelManager->persist($variant);
            $this->variants[$model->getModel()] = $variant;
            $product->addVariant($variant);
            // set properties which do not require mapping
            $variant->setIcNumber($model->getModel());
            $variant->setEan($model->getEan());
            $variant->setPurchasePrice($model->getPurchasePrice());
            $variant->setRecommendedRetailPrice($model->getRecommendedRetailPrice());
            $variant->setImages($model->getImages());

            $variant->setActive(false);
            $variant->setAccepted(true);
            $variant->setRetailPrices('EK' . MXC_DELIMITER_L1 . $model->getRecommendedRetailPrice());
            $variant->setOptions($this->mapOptions($model->getOptions()));
            $this->propertyMapper->mapModelToVariant($model, $variant);
        }
        $this->modelManager->flush();
    }

    /**
     * The Shopware details associated to obsolete variants must get removed before
     * the variants get deleted.
     *
     * @param array $deletions
     */
    protected function removeDetails(array $deletions)
    {
        $products = [];
        /** @var  Variant $variant */
        foreach ($deletions as $variant) {
            $variant->setAccepted(false);
            $product = $variant->getProduct();
            $products[$product->getIcNumber()] = $product;
        }
        $this->modelManager->flush();
        $this->productMapper->updateArticles($products);
    }

    /**
     * Remove all variants together with the Options and Images
     * belonging to them. Remove the Product also, if it has no variant left.
     *
     * @param array $deletions
     * @throws OptimisticLockException
     */
    protected function removeVariants(array $deletions)
    {
        $variantRepository = $this->getVariantRepository();

        // make sure that $this->products is initialized
        $this->getProducts();
        $this->getVariants();

        foreach ($deletions as $variant) {
            /** @var Product $product */
            $product = $variant->getProduct();
            $product->removeVariant($variant);
            $variant->setProduct(null);

            $variantRepository->removeOptions($variant);
            $this->modelManager->remove($variant);
            unset($this->variants[$variant->getIcNumber()]);
            if ($product->getVariants()->count === 0) {
                $this->modelManager->remove($product);
                unset($this->products[$product->getIcNumber()]);
            }
        }
        $this->modelManager->flush();
    }

    protected function deleteVariantsWithoutModel()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $deletions = $this->getVariantRepository()->getVariantsWithoutModel();
        if ( empty($deletions)) return;

        $this->removeDetails($deletions);
        $this->removeVariants($deletions);
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
        $variant->addOptions($addedOptions);
    }

    protected function changeVariant(Variant $variant, Model $model, array $fields)
    {
        $remap = false;
        foreach ($fields as $name => $values) {
            switch ($name) {
                // case 'category': Category is not currently used for mapping
                case 'manufacturer':
                case 'name':
                    $remap = true;
                    break;
                case 'description':
                    $variant->getProduct()->setIcDescription($model->getDescription());
                    break;
                case 'ean':
                    $ean = $model->getEan();
                    $variant->setEan($ean);
                    $detail = $variant->getDetail();
                    if ($detail) $detail->setEan($ean);
                    break;
                case 'recommendedRetailPrice':
                    $variant->setRecommendedRetailPrice($model->getRecommendedRetailPrice());
                    break;
                case 'purchasePrice':
                    $purchasePrice = $model->getPurchasePrice();
                    $variant->setPurchasePrice($purchasePrice);
                    $detail = $variant->getDetail();
                    if ($detail) $detail->setPurchasePrice($purchasePrice);
                    break;
                case 'images':
                    $variant->setImages($model->getImages());
                    break;
                case 'options':
                    $this->changeOptions($variant, $values['oldValue'], $values['newValue']);
                    break;
                default:
                    $this->log->debug(
                        sprintf("Untreated variant change: %s: %s (old value: '%s', new value: '%s')",
                            $model->getName(), $name, $values['oldValue'], $values['newValue']));
            }
            $this->log->info(sprintf("Changing variant: %s: %s changed from '%s' to '%s'",
                $model->getName(), $name, $values['oldValue'], $values['newValue']));
        }
        return $remap;
    }

    protected function changeExistingVariants(array $changes)
    {
        foreach ($changes as $icNumber => $change) {
            /** @var Variant $variant */
            $variant = $this->variants[$icNumber];
            $model = $change['model'];
            $fields = $change['fields'];
            $remap = $this->changeVariant($variant, $model, $fields);
            if (! $remap) continue;

            $product = $variant->getProduct();
            $this->updates[$product->getIcNumber()] = $product;
        }
        $this->modelManager->flush();
    }

    protected function removeOrphanedItems()
    {
        // this function will get obsolete once it is made sure that all removals work correctly
        $this->getVariantRepository()->removeOrphaned();
        $this->getProductRepository()->removeOrphaned();
        $this->getOptionRepository()->removeOrphaned();
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

        $this->options = $this->getOptionRepository()->getAllIndexed();
    }

    public function import(array $changes)
    {
        $this->updates = [];
        $this->initCache();

        $this->deleteVariantsWithoutModel();
        $this->addNewVariants();
        $this->changeExistingVariants($changes);
        $this->propertyMapper->mapProperties($this->updates);
        $this->removeOrphanedItems();

        $this->categoryMapper->buildCategoryTree();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->getProductRepository()->refreshProductStates();

        // $this->productMapper->updateArticles($this->updates);

        if (@$this->config['applyFilters']) {
            foreach ($this->config['filters']['update'] as $filter) {
                $this->bulkOperation->update($filter);
            }
        }

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
     * @return ModelRepository
     */
    protected function getModelRepository()
    {
        return $this->modelRepository ?? $this->modelRepository = $this->modelManager->getRepository(Model::class);
    }
}
