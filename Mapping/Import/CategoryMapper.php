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
    protected $report = [];

    protected $categoryMap;

    protected $classConfigFile = __DIR__ . '/../../Config/CategoryMapper.config.php';

    public function map(Model $model, Product $product) {
        $type = $product->getType();
        $categoryMap = $this->getCategoryMap();

        $category = $categoryMap[$type]['base_category'] ?? null;
        $categories = [];

        $flavorCategories = $product->getFlavorCategory();
        if ($flavorCategories) {
            $flavorCategories = array_map('trim', explode(',', $flavorCategories));
            foreach ($flavorCategories as $flavorCategory) {
                $categories[] = $category . ' > ' . $flavorCategory;
            }
        }

        $appendSubcategory = $categoryMap[$type]['append_subcategory'] ?? null;
        switch (is_string($appendSubcategory)) {
            case 'supplier':
                $appendSubcategory = $product->getSupplier();
                break;
            case 'brand':
                $appendSubcategory = $product->getBrand();
                break;
            case 'common_name':
                $appendSubcategory = $product->getCommonName();
                break;
            default:
                $appendSubcategory = null;
        }

        $categories[] = $appendSubcategory ? $category . ' > ' . $appendSubcategory : $category;

        if (! empty($categories)) {
            $category = implode(MXC_DELIMITER_L1, $categories);
            $product->setCategory($category);
            $this->report[$category][] = $product->getName();
        }
    }

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
        $products = $this->modelManager->getRepository(Product::class)->findAll();

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
        //ArrayTool::ksort_recursive($categoryTree);

        $categoryPositions = [];
        $this->updateCategoryPositions($categoryTree, $categoryPositions);
        $this->classConfig['category_tree'] = $categoryTree;
        $this->classConfig['category_positions'] = $categoryPositions;

        Factory::toFile($this->classConfigFile, $this->classConfig);
    }

    protected function getCategoryMap()
    {
        if ($this->categoryMap) return $this->categoryMap;
        $map = [];
        $typeMap = $this->classConfig['type_category_map'] ?? [];
        foreach ($typeMap as $item) {
            foreach ($item['types'] as $type) {
                $map[$type]['base_category'] = $item['base_category'];
                $map[$type]['append_subcategory'] = $item['append_subcategory'];
            }
        }
        $this->categoryMap = $map;
        return $map;
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