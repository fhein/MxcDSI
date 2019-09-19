<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
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
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;

class ImportMapper implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    protected $useCache = false;

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

    /** @var array $config */
    protected $config;

    /** @var array $options */
    protected $options;

    /** @var array $groups */
    protected $groups;

    /** @var array */
    protected $products;

    /** @var array */
    protected $variants;

    /** @var array */
    protected $updates;

    /**
     * ImportMapper constructor.
     *
     * @param ArticleTool $articleTool
     * @param PropertyMapper $propertyMapper
     * @param CategoryMapper $categoryMapper
     * @param ProductMapper $productMapper
     * @param DetailMapper $detailMapper
     */
    public function __construct(
        ArticleTool $articleTool,
        PropertyMapper $propertyMapper,
        CategoryMapper $categoryMapper,
        ProductMapper $productMapper,
        DetailMapper $detailMapper
    ) {
        $this->articleTool = $articleTool;
        $this->detailMapper = $detailMapper;
        $this->categoryMapper = $categoryMapper;
        $this->propertyMapper = $propertyMapper;
        $this->productMapper = $productMapper;
    }

    /**
     * @param string $groupName
     * @return Group
     */
    protected function addGroup(string $groupName): Group
    {
        $group = new Group();
        $this->modelManager->persist($group);
        $group->setAccepted(true);
        $group->setName($groupName);
        return $group;
    }

    protected function mapGroup(string $groupName)
    {
        $group = @$this->groups[$groupName];
        if ($group) return $group;
        if ($this->useCache) {
            $group = $this->addGroup($groupName);
        } else {
            $group = $this->getGroupRepository()->findOneBy(['name' => $groupName]) ?? $this->addGroup($groupName);
        }
        $this->groups[$groupName] = $group;
        return $group;
    }

    public function mapOptions(?string $optionString): ArrayCollection
    {
        if ($optionString === null) return new ArrayCollection();
        $optionArray = explode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $optionString);
        $options = [];
        foreach ($optionArray as $option) {
            $param = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $option);
            $optionName = $this->propertyMapper->mapOptionName($param[1]);
            $groupName = $this->propertyMapper->mapGroupName($param[0]);
            $option = @$this->options[$groupName][$optionName];
            if ($option === null && ! $this->useCache) {
                $option = $this->getOptionRepository()->getOption($groupName, $optionName);
            }
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
        $product->setManufacturer($model->getManufacturer());
        $product->setIcDescription($model->getDescription());
        $this->propertyMapper->mapModelToProduct($model, $product, true);
        $this->log->info('New product ' . $product->getIcNumber() . ': ' . $product->getName());

        return $product;
    }

    protected function mapProduct(Model $model)
    {
        $number = $model->getMaster();

        // return cached product if available
        $product = @$this->products[$number];
        if ($product) return $product;

        if ($this->useCache) {
            $product = $this->addProduct($model);
        } else {
            $product = $this->getProductRepository()->findOneBy(['icNumber' => $number]) ?? $this->addProduct($model);
        }

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
            $product->addVariant($variant);
            // set properties which do not require mapping
            $variant->setIcNumber($model->getModel());
            $variant->setName($model->getName());
            $variant->setEan($model->getEan());
            $variant->setPurchasePrice(str_replace(',', '.', $model->getPurchasePrice()));
            $recommendedRetailPrice = str_replace(',', '.', $model->getRecommendedRetailPrice());
            $variant->setRecommendedRetailPrice($recommendedRetailPrice);
            $variant->setImages($model->getImages());
            $unit = $model->getUnit();
            if (! empty($unit)) $variant->setUnit($unit);
            $content = $model->getContent();
            if (! empty($content)) $variant->setContent($content);

            $active = $product->isActive() && $this->isSinglePack($model);

            $variant->setActive($active);
            $variant->setAccepted(true);
            $variant->setRetailPrices('EK' . MxcDropshipInnocigs::MXC_DELIMITER_L1 . $recommendedRetailPrice);
            $options = $this->mapOptions($model->getOptions());
            $variant->setOptions($options);
            $this->propertyMapper->mapModelToVariant($model, $variant);
        }
        $this->modelManager->flush();
    }

    protected function isSinglePack(Model $model)
    {
        $options = $model->getOptions();

        $pattern = 'PACKUNG' . MxcDropshipInnocigs::MXC_DELIMITER_L1;
        if (strpos($options, $pattern) === false) return true;

        $pattern .= '1er Packung';
        if (strpos($model->getOptions(), $pattern) !== false) return true;

        return false;
    }


    /**
     * The Shopware details associated to obsolete variants must get removed before
     * the variants get deleted.
     */
    protected function removeDetails()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $deletions = $this->getVariantRepository()->getVariantsWithoutModel();
        if ( empty($deletions)) return;

        $products = [];
        /** @var  Variant $variant */
        foreach ($deletions as $variant) {
            $variant->setAccepted(false);
            $product = $variant->getProduct();
            $products[$product->getIcNumber()] = $product;
        }

        $this->productMapper->updateArticles($products);
    }

    /**
     * Remove all variants together with the Options and Images
     * belonging to them. Remove the Product also, if it has no variant left.
     */
    protected function removeVariants()
    {
        $variantRepository = $this->getVariantRepository();
        /** @noinspection PhpUndefinedMethodInspection */
        $deletions = $variantRepository->getVariantsWithoutModel();
        if (empty($deletions)) return;

        /** @var Variant $variant */
        foreach ($deletions as $variant) {
            /** @var Product $product */
            $product = $variant->getProduct();
            $product->removeVariant($variant);

            $variantRepository->removeOptions($variant);
            $this->modelManager->remove($variant);
            if ($product->getVariants()->count === 0) {
                $this->modelManager->remove($product);
            }
        }
        $this->modelManager->flush();
    }

    protected function deleteVariantsWithoutModel()
    {
        /** @noinspection PhpUndefinedMethodInspection */

        $this->removeDetails();
        $this->removeVariants();
    }

    protected function changeOptions(Variant $variant, string $oldValue, string $newValue)
    {
        $oldOptions = explode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $oldValue);
        $newOptions = explode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $newValue);
        $rOptions = array_diff($oldOptions, $newOptions);
        foreach ($rOptions as $option) {
            if ($option === null) continue;
            $param = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $option);
            $optionName = $this->propertyMapper->mapOptionName($param[1]);
            $groupName = $this->propertyMapper->mapGroupName($param[0]);

            $variantOptions = $variant->getOptions();
            foreach ($variantOptions as $variantOption){
                $name = $variantOption->getName();
                $group = $variantOption->getICGroup()->getName();
                if($group == $groupName && $name == $optionName) $variant->removeOption($variantOption);
            }
        }
        $addedOptions = array_diff($newOptions, $oldOptions);
        $addedOptions = implode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $addedOptions);
        $variant->addOptions($this->mapOptions($addedOptions));
    }

    protected function changeVariant(Variant $variant, Model $model, array $fields)
    {
        $remap = false;
        foreach ($fields as $name => $values) {
            switch ($name) {
                // case 'category': Category is not currently used for mapping
                case 'manufacturer':
                case 'productName':
                    $remap = true;
                    break;
                case 'description':
                    $product = $variant->getProduct();
                    $oldIcDescription = $product->getIcDescription();
                    $newIcDescription = $model->getDescription();
                    $product->setIcDescription($newIcDescription);
                    // update product description if it uses the text provided by InnoCigs
                    if ($product->getDescription() === $oldIcDescription) {
                        $product->setDescription($newIcDescription);
                    }
                    break;
                case 'ean':
                    $ean = $model->getEan();
                    $variant->setEan($ean);
                    $detail = $variant->getDetail();
                    if ($detail !== null) $detail->setEan($ean);
                    break;
                case 'recommendedRetailPrice':
                    $recommendedRetailPrice = str_replace(',', '.', $model->getRecommendedRetailPrice());
                    $variant->setRecommendedRetailPrice($recommendedRetailPrice);
                    break;
                case 'purchasePrice':
                    $purchasePrice = str_replace(',', '.', $model->getPurchasePrice());
                    $variant->setPurchasePrice($purchasePrice);
                    $detail = $variant->getDetail();
                    if ($detail !== null) $detail->setPurchasePrice(floatval($purchasePrice));
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
            if ($this->useCache) {
                $variant = $this->variants[$icNumber];
            } else {
                $variant = $this->getVariantRepository()->findOneBy(['icNumber' => $icNumber]);
            }
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
        $this->modelManager->createQuery('UPDATE ' . Variant::class . ' a '
            . 'set a.new = false, a.recommendedRetailPriceOld = a.recommendedRetailPrice, '
            . 'a.purchasePriceOld = a.purchasePrice')->execute();
        if ($this->useCache) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->products = $this->getProductRepository()->getAllIndexed();
            /** @noinspection PhpUndefinedMethodInspection */
            $this->groups = $this->getGroupRepository()->getAllIndexed();
            $this->options = $this->getOptionRepository()->getAllIndexed();
            /** @noinspection PhpUndefinedMethodInspection */
            $this->variants = $this->getVariantRepository()->getAllIndexed();
        }
    }

    public function import(array $changes)
    {
        $this->updates = [];
        $this->initCache();

        $this->addNewVariants();
        $this->changeExistingVariants($changes);
        $this->deleteVariantsWithoutModel();
        $this->propertyMapper->mapProperties($this->updates, true);
        $this->removeOrphanedItems();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->getProductRepository()->refreshProductStates();

        $this->productMapper->updateArticles($this->updates);

        return true;
    }

    public function updateFromModel(bool $updateAll, Array $ids = null)
    {
        $this->initCache(); //sets "new" fields

        $model = new Model();
        $modelfields = $model->getPrivatePropertyNames();

        //get products to change
        if ($updateAll){

        }else{
            $products = $this->getProductRepository()->getProductsByIds($ids);
        }

        $changes = [];


        $modelClass = new \ReflectionClass('MxcDropshipInnocigs\Models\Model');

        foreach ($products as $product) {

            //get all variants
            $variants = $product->getVariants();

            foreach ($variants as $variant) {
                //get models
                $model = $this->getModelRepository()->findOneBy(['model' => $variant->getNumber()]);

                $number = $model->getModel();
                $fields = [];
                foreach ($modelfields as $field) {

                        $property = $modelClass->getProperty($field);
                        $property->setAccessible(true);

                    if($field == 'options') {
                        //$param = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $property->getValue($model));

                        $oldOptions = $variant->getOptions();
                        $oldOptionString = [];
                        foreach($oldOptions as $oldOption){
                            $oldGroupName = $this->propertyMapper->unMapGroupName($oldOption->getICGroup()->getName());
                            //$oldOptionString .= MxcDropshipInnocigs::MXC_DELIMITER_L1;
                            $oldOptionName = $this->propertyMapper->unMapOptionName($oldOption->getName());
                            array_push($oldOptionString,$oldGroupName . MxcDropshipInnocigs::MXC_DELIMITER_L1 . $oldOptionName);
                        }
                        $oldOptions = implode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $oldOptionString);


                        $fields[$field] = [
                            'oldValue' => $oldOptions,
                            'newValue' => $property->getValue($model)
                        ];
                    }else {

                        $fields[$field] = [
                            'oldValue' => '*unknown*',
                            'newValue' => $property->getValue($model)
                        ];
                    }
                }
                if (!empty($fields)) {
                    $changes[$number] = [
                        'model'  => $model,
                        'fields' => $fields,
                    ];
                }
            }
    }

        $this->changeExistingVariants($changes);
        $this->propertyMapper->mapProperties($this->updates, true);

        //$this->modelManager->flush();


    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->productRepository ?? $this->productRepository = $this->modelManager->getRepository(Product::class);
    }

    /**
     * @return VariantRepository
     */
    protected function getVariantRepository()
    {
        return $this->variantRepository ?? $this->variantRepository = $this->modelManager->getRepository(Variant::class);
    }

    /**
     * @return GroupRepository
     */
    protected function getGroupRepository()
    {
        return $this->groupRepository ?? $this->groupRepository = $this->modelManager->getRepository(Group::class);
    }

    /**
     * @return OptionRepository
     */
    protected function getOptionRepository()
    {
        return $this->optionRepository ?? $this->optionRepository = $this->modelManager->getRepository(Option::class);
    }

    /**
     * @return ModelRepository
     */
    protected function getModelRepository()
    {
        return $this->modelRepository ?? $this->modelRepository = $this->modelManager->getRepository(Model::class);
    }
}
