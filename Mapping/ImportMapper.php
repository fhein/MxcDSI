<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Mapping\Import\CategoryMapper;
use MxcDropshipIntegrator\Mapping\Import\PropertyMapper;
use MxcDropshipIntegrator\Mapping\Shopware\DetailMapper;
use MxcDropshipIntegrator\Models\Group;
use MxcDropshipIntegrator\Models\GroupRepository;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\ModelRepository;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\OptionRepository;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\ProductRepository;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\Models\VariantRepository;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcCommons\Toolbox\Shopware\TaxTool;

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
        $optionArray = explode(MxcDropshipIntegrator::MXC_DELIMITER_L2, $optionString);
        $options = [];
        foreach ($optionArray as $option) {
            $param = explode(MxcDropshipIntegrator::MXC_DELIMITER_L1, $option);
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
        $product->setTax(TaxTool::getCurrentVatPercentage());
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

            $purchasePrice = floatval(str_replace(',', '.', $model->getPurchasePrice()));
            $variant->setPurchasePrice($purchasePrice);

            $uvp = floatval(str_replace(',', '.', $model->getRecommendedRetailPrice()));
            $vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;
            $variant->setRecommendedRetailPrice($uvp / $vatFactor);

            $variant->setImages($model->getImages());
            $unit = $model->getUnit();
            if (! empty($unit)) $variant->setUnit($unit);
            $content = $model->getContent();
            if (! empty($content)) $variant->setContent($content);

            $active = $product->isActive() && $this->isSinglePack($model);

            $variant->setActive($active);
            $variant->setAccepted(true);
            $variant->setRetailPrices('EK' . MxcDropshipIntegrator::MXC_DELIMITER_L1 . $uvp);
            $options = $this->mapOptions($model->getOptions());
            $variant->setOptions($options);
            $this->propertyMapper->mapModelToVariant($model, $variant);
        }
        $this->modelManager->flush();
    }

    protected function isSinglePack(Model $model)
    {
        $options = $model->getOptions();

        $pattern = 'PACKUNG' . MxcDropshipIntegrator::MXC_DELIMITER_L1;
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
        $oldOptions = explode(MxcDropshipIntegrator::MXC_DELIMITER_L2, $oldValue);
        $newOptions = explode(MxcDropshipIntegrator::MXC_DELIMITER_L2, $newValue);
        $rOptions = array_diff($oldOptions, $newOptions);
        foreach ($rOptions as $option) {
            if ($option === null) continue;
            $param = explode(MxcDropshipIntegrator::MXC_DELIMITER_L1, $option);
            $o = $this->options[$param[0]][$param[1]];
            if ($o !== null) {
                $variant->removeOption($o);
            }
        }
        $addedOptions = array_diff($newOptions, $oldOptions);
        $addedOptions = implode(MxcDropshipIntegrator::MXC_DELIMITER_L2, $addedOptions);
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
                    $uvp = floatval(str_replace(',', '.', $model->getRecommendedRetailPrice()));
                    // @todo: Change price datatype in database to float
                    $vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;
                    $uvp = strval(round($uvp / $vatFactor, 2));
                    $variant->setRecommendedRetailPrice($uvp);
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

    protected function getProductRepository()
    {
        return $this->productRepository ?? $this->productRepository = $this->modelManager->getRepository(Product::class);
    }

    protected function getVariantRepository()
    {
        return $this->variantRepository ?? $this->variantRepository = $this->modelManager->getRepository(Variant::class);
    }

    protected function getGroupRepository()
    {
        return $this->groupRepository ?? $this->groupRepository = $this->modelManager->getRepository(Group::class);
    }

    protected function getOptionRepository()
    {
        return $this->optionRepository ?? $this->optionRepository = $this->modelManager->getRepository(Option::class);
    }

    protected function getModelRepository()
    {
        return $this->modelRepository ?? $this->modelRepository = $this->modelManager->getRepository(Model::class);
    }
}
