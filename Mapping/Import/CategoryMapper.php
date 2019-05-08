<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;
use Zend\Config\Factory;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class CategoryMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    /** @var array */
    protected $report;

    protected $classConfigFile = __DIR__ . '/../../Config/CategoryMapper.config.php';

    public function map(Model $model, Product $product) {
        $type = $product->getType();
        $category = $this->classConfig['product_type_category_map'][$type] ?? null;
        $categories = [];

        switch ($type) {
            case 'LIQUID':
            case 'AROMA':
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'SHAKE_VAPE':
                $flavorCategories = $product->getFlavorCategory();
                if ($flavorCategories) {
                    $flavorCategories = array_map('trim', explode(',', $flavorCategories));
                    foreach ($flavorCategories as $flavorCategory) {
                        $categories[] = $this->addSubCategory($category, $flavorCategory);
                    }
                }
                $categories[] = $this->addSubCategory($category, $product->getBrand());
                break;

            case 'E_PIPE':
            case 'E_CIGARETTE':
            case 'BOX_MOD':
            case 'BOX_MOD_CELL':
            case 'SQUONKER_BOX':
            case 'CLEAROMIZER':
            case 'HEAD':
            case 'DRIP_TIP':
            case 'TANK':
            case 'SEAL':
            case 'CLEAROMIZER_RTA':
            case 'CLEAROMIZER_RDA':
            case 'CLEAROMIZER_RDTA':
            case 'CLEAROMIZER_RDSA':
                $categories[] = $this->addSubCategory($category, $product->getSupplier());
                break;

            case 'CARTRIDGE':
            case 'POD':
            case 'BATTERY_CAP':
            case 'BATTERY_SLEEVE':
            case 'MAGNET':
            case 'MAGNET_ADAPTOR':
                $categories[] = $this->addSubCategory($category, $product->getCommonName());
                break;
            default:
                $categories[] = $category;
        }
        if (! empty($categories)) {
            $category = implode(MXC_DELIMITER_L1, $categories);
            $product->setCategory($category);
            $this->report[$category][] = $product->getName();
        }
    }

    protected function addSubCategory(string $category, ?string $subCategory)
    {
        return $subCategory = null ? $category : $category . ' > ' . $subCategory;
    }

    /**
     * Create
     *
     * @param array $categoryTree
     * @param array $positions
     * @param string|null $path
     */
    protected function updateCategoryPositions(array $categoryTree, array &$positions, string $path = null)
    {
        $idx = 1;
        foreach ($categoryTree as $key => $value) {
            $pathKey = $path ? $path . ' > ' . $key : $key;
            $positions[$pathKey] = $idx++;
            if (is_array($value)) {
                $this->updateCategoryPositions($categoryTree[$key], $positions, $pathKey);
            }
        }
    }

    /**
     * Update an existing category tree from a new category tree. This function maintains the category sort order
     * of the existing tree.
     *
     * @param array $categoryTree
     * @param array $newTree
     */
    protected function updateCategoryTree(array &$categoryTree, array $newTree)
    {
        $obsoleteKeys = array_diff(array_keys($categoryTree), array_keys($newTree));
        foreach ($obsoleteKeys as $key) {
            unset($categoryTree[$key]);
        }
        foreach (array_keys($newTree) as $key) {
            if (is_array($newTree[$key]) && is_array($categoryTree[$key])) {
                $this->updateCategoryTree($categoryTree[$key], $newTree[$key]);
            } else {
                $categoryTree[$key] = $newTree[$key];
            }
        }
    }

    /**
     * Create a nested category array from the product's categories. Sets the number of articles
     * for each leaf category.
     *
     * @return array
     */
    protected function createCategoryTree()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();

        $newTree = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $categories = explode(MXC_DELIMITER_L1, $product->getCategory());
            foreach ($categories as $category) {
                $path = array_map('trim', explode('>', $category));
                $temp = &$newTree;
                foreach ($path as $idx) {
                    $temp = &$temp[$idx];
                }
                $temp++;
                unset($temp);
            }
        }
        return $newTree;
    }

    public function buildCategoryTree()
    {
        $categoryTree = $this->classConfig['category_tree'] ?? [];

        $newTree = $this->createCategoryTree();
        $this->updateCategoryTree($categoryTree, $newTree);

        // Use ArrayTool and enable the next line to enforce a recursive alphabetical sort of all categories in the tree
        // $result = ArrayTool::ksort_recursive($categoryTree);

        $categoryPositions = [];
        $this->updateCategoryPositions($categoryTree, $categoryPositions);
        $this->classConfig['category_tree'] = $categoryTree;
        $this->classConfig['category_positions'] = $categoryPositions;

        Factory::toFile($this->classConfigFile, $this->classConfig);
    }

    public function report()
    {
        ksort($this->report);
        foreach ($this->report as &$array) {
            sort($array);
        }

        (new ArrayReport())([
            'pmCategoryUsage' => $this->report,
            'pmCategory'      => array_keys($this->report),
        ]);
   }
}