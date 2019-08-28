<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;
use Psr\Log\LoggerAwareTrait;
use Zend\Config\Factory;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class CategoryMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var array */
    protected $report = [];

    protected $typeMap;
    protected $classConfigFile = __DIR__ . '/../../Config/CategoryMapper.config.php';

    protected $categorySeoItems;

    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $category = @$this->config[$product->getIcNumber()]['category'];
        if ($remap || $category === null) {
            $this->remap($product);
            return;
        }
        $product->setCategory($category);
    }

    public function remap(Product $product)
    {
        $type = $product->getType();
        if (empty($type)) return null;
        $map =  $this->classConfig['type_category_map'];
        $typeMap = $this->getTypeMap();
        $category = $map[$typeMap[$type]]['path'];
        $this->log->debug($product->getName());
        $this->log->debug($type);
        $this->log->debug($category);
        $this->classConfig['type_category_map'][$typeMap[$type]];

        $flavorCategories = $this->getFlavorCategories($product, $category);
        $additionalCategories = $this->getAdditionalCategories($product);

        $subCategory = $this->getSubCategory($product);

        $categories = $flavorCategories + $additionalCategories;
        $path = $subCategory ? $category . ' > ' . $subCategory : $category;
        $categories[] = $path;

        $this->getCategorySeoItems($product, $path);
        if (! empty($flavorCategories)) {
            $this->getFlavorCategorySeoItems($flavorCategories);
        }

        $category = null;
        if (! empty($categories)) {
            $category = implode(MXC_DELIMITER_L1, $categories);
            $product->setCategory($category);
            $this->report[$category][] = $product->getName();
        }
        $product->setCategory($category);
    }

    protected function updateCategoryPositions(array $categoryTree, array &$positions, string $path = null)
    {
        $idx = 2;
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

    protected function getTypeMap() {
        if (! empty($this->typeMap)) return $this->typeMap;
        $typeMap = [];
        foreach ($this->classConfig['type_category_map'] as $idx => $record) {
            foreach ($record['types'] as $type) {
                $typeMap[$type] = $idx;
            }
        }
        return $this->typeMap = $typeMap;
    }


    public function report()
    {
        ksort($this->report);
        foreach ($this->report as &$array) {
            sort($array);
        }

        (new ArrayReport())([
            'pmCategoryUsage' => $this->report ?? [],
            'pmCategory'      => array_keys($this->report) ?? [],
            'pmSeoCategories' => $this->categorySeoItems ?? [],
        ]);

        $seoConfig = $this->classConfig['category_seo_items'] ?? [];
        $this->classConfig['category_seo_items'] = array_replace_recursive($seoConfig, $this->categorySeoItems);
        Factory::toFile($this->classConfigFile, $this->classConfig);

   }

    /**
     * @param Product $product
     * @param $category
     * @return array
     */
    protected function getFlavorCategories(Product $product, $category): array
    {
        $categories = [];
        $flavorCategories = $product->getFlavorCategory();
        if (empty($flavorCategories)) return $categories;

        $flavorCategories = array_map('trim', explode(',', $flavorCategories));
        foreach ($flavorCategories as $flavorCategory) {
            $categories[] = $category . ' > ' . $flavorCategory;
        }
        return $categories;
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getAdditionalCategories(Product $product): array
    {
        $categories = [];
        $addlCategories = $product->getAddlCategory();
        if (empty($addlCategories)) return $categories;

        $addlCategories = array_map('trim', explode(',', $addlCategories));
        foreach ($addlCategories as $addlCategory) {
            $categories[] = $addlCategory;
        }
        return $categories;
    }

    /**
     * @param Product $product
     * @return mixed|string|null
     */
    protected function getSubCategory(Product $product)
    {
        $type = $product->getType();
        $subCategory = $this->classConfig['type_category_map'][$this->typeMap[$type]]['append'] ?? null;
        if (empty($subCategory)) return null;

        switch ($subCategory) {
            case 'supplier':
                $subCategory = $product->getSupplier();
                if ($subCategory === 'InnoCigs') {
                    $subCategory = $product->getBrand();
                }
                break;
            case 'brand':
                $subCategory = $product->getBrand();
                break;
            case 'common_name':
                $subCategory = $product->getCommonName();
                break;
            default:
                $subCategory = null;
        }
        return $subCategory;
    }

    protected function getFlavorCategorySeoItems(array $pathes)
    {
        foreach ($pathes as $path) {
            if (! empty($this->categorySeoItems[$path])) continue;

            $pathItems = array_map('trim', explode('>', $path));
            $profile = $this->classConfig['flavor_category_map'][$pathItems[0]];
            $flavor = $pathItems[1];
            $h1 = strtoupper(str_replace('##flavor##', $flavor, $profile['h1']));
            $title = str_replace('##flavor##', $flavor, $profile['title']);
            $description = str_replace('##flavor##', $flavor, $profile['description']);
            $keywords = str_replace('##flavor##', $flavor, $profile['keywords']);

            $this->categorySeoItems[$path] = [
                'seo_h1' => $h1,
                'seo_title' => $title,
                'seo_description' => $description,
                'seo_keywords' => $keywords,
            ];
        }
    }

    protected function getCategorySeoItems(Product $product, string $path)
    {
        $map = $this->classConfig['type_category_map'];
        $pathLen = strlen($path);
        $pathItems = array_map('trim', explode('>', $path));

        $type = $product->getType();
        $supplier = $product->getSupplier();
        if ($supplier === 'InnoCigs') {
            $supplier = $product->getBrand();
        }
        $brand = $product->getBrand();
        $commonName = $product->getCommonName();

        $p = null;
        foreach ($pathItems as $item) {
            $p = $p === null ? $item : $p . ' > ' . $item;
            if (strlen($p) === $pathLen) {
                $seoIndex = $map[$this->typeMap[$type]]['append'] ?? $item;
            } else {
                $seoIndex = $item;
            }
            $seoSettings = $map[$this->typeMap[$type]]['seo'][$seoIndex];

            if (empty($seoSettings)) return;
            if (! empty($this->categorySeoItems[$p])) continue;

            $title = $seoSettings['title'] ?? null;
            if ($title !== null) {
                $title = str_replace(['##supplier##', '##brand##', '##common_name##'], [$supplier, $brand, $commonName],
                    $title);
            }
            $description = $seoSettings['description'] ?? null;
            if ($description !== null) {
                $description = str_replace(['##supplier##', '##brand##', '##common_name##'],
                    [$supplier, $brand, $commonName], $description);
            }

            $keywords = $seoSettings['keywords'] ?? null;
            if ($keywords !== null) {
                $keywords = str_replace(['##supplier##', '##brand##', '##common_name##'],
                    [$supplier, $brand, $commonName], $keywords);
            }

            $h1 = $seoSettings['h1'];
            if ($h1 !== null) {
                $h1 = strtoupper(str_replace(['##supplier##', '##brand##', '##common_name##'],
                    [$supplier, $brand, $commonName], $h1));
            }
            $this->categorySeoItems[$p] = [
                'seo_title'       => $title,
                'seo_description' => $description,
                'seo_keywords'    => $keywords,
                'seo_h1'          => $h1,
            ];
        }
    }
}