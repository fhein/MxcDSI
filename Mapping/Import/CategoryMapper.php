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

    public function map(Model $model, Product $product) {
        $type = $product->getType();
        $category = $this->classConfig['categories'][$type] ?? null;
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
                // intentional fall through
            case 'BASE':
            case 'SHOT':
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
            case 'TOOL':
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

    protected function setupCategoryPositions(array $categoryTree, array &$positions, string $path = null)
    {
        $idx = 1;
        foreach ($categoryTree as $key => $value) {
            $pathKey = $path ? $path . ' > ' . $key : $key;
            $positions[$pathKey] = $idx++;
            if (is_array($value)) {
                $this->setupCategoryPositions($categoryTree[$key], $positions, $pathKey);
            }
        }
    }

    protected function setupCategoryTree(array &$categoryTree, array $newTree)
    {
        $obsoleteKeys = array_diff(array_keys($categoryTree), array_keys($newTree));
        foreach ($obsoleteKeys as $key) {
            unset($categoryTree[$key]);
        }
        foreach (array_keys($newTree) as $key) {
            if (is_array($categoryTree[$key])) {
                $this->setupCategoryTree($categoryTree[$key], $newTree[$key]);
            } else {
                $categoryTree[$key] = $newTree[$key];
            }
        }
    }

    protected function updateCategoryTree() {
        $categoryTreeFile = __DIR__ . '/../../Config/category.tree.php';

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

        $categoryTree = file_exists($categoryTreeFile) ? Factory::fromFile($categoryTreeFile) : [];
        $this->setupCategoryTree($categoryTree, $newTree);
        $positions = [];
        $this->setupCategoryPositions($categoryTree, $positions);
        $this->log->debug('Category positions: ' . var_export($positions, true));

        Factory::toFile($categoryTreeFile,
            [
                'category_tree' => $categoryTree,
                'category_positions' => $positions,
            ]
        );
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

        $this->updateCategoryTree();
    }
}