<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Category;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;

class CategoryMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var array */
    protected $report = [];

    protected $typeMap;
    protected $classConfigFile = __DIR__ . '/../../Config/CategoryMapper.config.php';

    protected $categorySeoItems;
    protected $categoryRepository;

    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $category = @$this->config[$product->getIcNumber()]['category'];
        $remap = true;
        if ($remap || $category === null) {
            $this->remap($product);
            return;
        }
        $product->setCategory($category);
    }

    public function remap(Product $product)
    {

        $categories = $this->getCategoryPathes($product);
        $this->getCategorySeoItems($product);

//        $this->getCategorySeoItems($product, $path);
//        if (! empty($flavorCategories)) {
//            $this->getFlavorCategorySeoItems($flavorCategories);
//        }
        $category = null;
        if (! empty($categories)) {
            // remove empty entries
            $categories = array_filter(array_map('trim', $categories));
            if (! empty($categories)) {
                $category = implode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $categories);
                $this->report[$category][] = $product->getName();
            }
        } else {
            $this->log->warn('Product without category: ' . $product->getName());
        }
        $product->setCategory($category);
    }

    public function sortCategories()
    {
        $services = MxcDropshipInnocigs::getServices();
        $categoryTool = $services->get(CategoryTool::class);
        $parentPathes = @$this->classConfig['sort_child_categories'];
        $root = $categoryTool->findCategoryPath('Deutsch', null);
        foreach ($parentPathes as $parentPath) {
            $parent = $categoryTool->findCategoryPath($parentPath, $root);
            if ($parent !== null) {
                $categoryTool->sortSubCategories($parent);
            }
        }
    }

    /**
     * @param Product $product
     * @return mixed|string|null
     */
    protected function getCategoryPathes(Product $product)
    {
        $categories = [];
        $type = $product->getType();
        if (empty($type)) return $categories;

        $typeMap = $this->getTypeMap();
        $map = $this->classConfig['type_category_map'][$typeMap[$type]] ?? null;
        if ($map === null) return $categories;

        $path = $map['path'] ?? null;
        if ($path === null) return $categories;

        $append = $map['append'] ?? null;
        if ($append === null) return [$path];

        $subCategories = [];
        foreach ($append as $subCategoryType) {
            switch ($subCategoryType) {
                case 'supplier':
                    $supplier = $product->getSupplier();
                    if ($supplier === 'InnoCigs') {
                        $supplier = $product->getBrand();
                    }
                    $subCategories[] = $supplier;
                    break;
                case 'brand':
                    $subCategories[] = $product->getBrand();
                    break;
                case 'common_name':
                    $subCategories[] = $product->getCommonName();
                    break;
                case 'flavor':
                    $flavorGroups = array_map('trim', explode(',', $product->getFlavorCategory()));
                    $subCategories = array_replace($subCategories, $flavorGroups);
                    break;
                case 'pod-system':
                    $subCategories = array_replace($subCategories, $this->getPodSystemAppendices($product));
                    break;
                case 'e-cigarette':
                    $subCategories = array_replace($subCategories, $this->getEcigAppendices($product));
                    break;
            }
        }
        foreach ($subCategories as $subCategory) {
            if (empty($subCategory)) continue;
            $categories[] = $path . ' > ' . $subCategory;
        }
        return $categories;
    }

    protected function getEcigAppendices(Product $product)
    {
        $categories = [];

        // cell capacity
        $capacity = $product->getCellCapacity();
        if (empty($capacity)) {
            $cellCount = $product->getCellCount();
            if ($cellCount == 1) {
                $categories[] = 'für eine Akkuzelle';
            } elseif ($cellCount == 2) {
                $categories[] = 'für zwei Akkuzellen';
            } elseif ($cellCount == 3) {
                $categories[] = 'für mehr Akkuzellen';
            }
        } elseif ($capacity <= 500) {
            $categories[] = 'Akku bis 500 mAh';
        } elseif ($capacity <= 1000) {
            $categories[] = 'Akku bis 1.000 mAh';
        } elseif ($capacity <= 1500) {
            $categories[] = 'Akku bis 1.500 mAh';
        } elseif ($capacity <= 2000) {
            $categories[] = 'Akku bis 2.000 mAh';
        } elseif ($capacity <= 2500) {
            $categories[] = 'Akku bis 2.500 mAh';
        } else {
            $categories[] = 'Akku über 2.500 mAh';
        }

        // tank capacity
        $capacity = $product->getCapacity();
        $capacity = floatval(str_replace(',', '.', $capacity));
        if ($capacity <= 2.0) {
            $categories[] = 'Tank bis 2 ml';

        } elseif ($capacity <= 4.0) {
            $categories[] = 'Tank bis 4 ml';
        } else {
            $categories[] = 'Tank über 4 ml';
        }

        // power
        $power = $product->getPower();
        if ($power != 0 && $power !== null)
        {
            if ($power <= 25) {
                $categories[] = 'bis 25 Watt';
            } elseif ($power <= 75) {
                $categories[] = 'bis 75 Watt';
            } elseif ($power <= 120) {
                $categories[] = 'bis 120 Watt';
            } else {
                $categories[] = 'über 120 Watt';
            }
        }

        return $categories;
    }

    protected function getPodSystemAppendices(Product $product)
    {
        $categories = [];

        // cell capacity
        $capacity = $product->getCellCapacity();
        if (empty($capacity)) {
            $categories[] = 'Akku wechselbar';
        } elseif ($capacity <= 500) {
            $categories[] = 'Akku bis 500 mAh';
        } elseif ($capacity <= 1000) {
            $categories[] = 'Akku bis 1.000 mAh';
        } else {
            $categories[] = 'Akku über 1.000 mAh';
        }

        // intrated or changeable head
        $headChangeable = $product->isHeadChangeable();
        if ($headChangeable) {
            $categories[] = 'Kopf wechselbar';
        } else {
            $categories[] = 'Kopf integriert';
        }

        // tank capacity
        $capacity = $product->getCapacity();
        $capacity = floatval(str_replace(',', '.', $capacity));
        if ($capacity <= 2.0) {
            $categories[] = 'Tank bis 2 ml';

        } elseif ($capacity <= 4.0) {
            $categories[] = 'Tank bis 4 ml';
        } else {
            $categories[] = 'Tank über 4 ml';
        }
        return $categories;
    }

    protected function getCategorySeoItems(Product $product)
    {
        $type = $product->getType();
        if (empty($type)) return;

        $typeMap = $this->getTypeMap();
        $map = $this->classConfig['type_category_map'][$typeMap[$type]] ?? null;
        if ($map === null) return;

        $path = $map['path'] ?? null;
        if ($path === null) return;

        $this->getBasePathSeoItems($path);

        $append = $map['append'] ?? null;
        if ($append === null) return;

        $brand = $product->getBrand();
        $supplier = $product->getSupplier();
        if ($supplier === 'InnoCigs') {
            $supplier = $brand;
        }
        $commonName = $product->getCommonName();
        $flavorGroups = $product->getFlavorCategory();

        $subCategoryAppendices = [
            'supplier' => [$supplier],
            'brand' => [$brand],
            'common_name' => [$commonName],
            'flavor' => array_map('trim', explode(',',$flavorGroups))
        ];
        if ($type === 'POD_SYSTEM') {
            $subCategoryAppendices['pod-system'] = $this->getPodSystemAppendices($product);
        } elseif ($type === 'E_CIGARETTE' || $type === 'E_PIPE') {
            $subCategoryAppendices['e-cigarette'] = $this->getEcigAppendices($product);
        }
        $categoryRepository = $this->getCategoryRepository();

        foreach ($append as $subCategoryType) {

            $appendices = $subCategoryAppendices[$subCategoryType] ?? null;
            if (! $appendices) continue;

            // ***!*** dies funktioniert nicht bei Pod-Systemen
            // weil deren seo Beschreibung woanders gespeichert ist
            $seoSettings = $map['seo'][$subCategoryType];
            if (empty($seoSettings)) continue; // hier leider exit für pod-systeme

            foreach ($appendices as $appendix) {
                if (empty($appendix)) continue;

                $idx = $path . ' > ' . $appendix;
                if (isset($this->categorySeoItems[$idx])) continue;

                $title = $seoSettings['title'] ?? null;
                if ($title !== null) {
                    $title = str_replace(['##supplier##', '##brand##', '##common_name##','##flavor##'], [$supplier, $brand, $commonName, $appendix], $title);
                    //--- workaround for Elli's Aromen
                    $title = str_replace ('Aromen Aromen', 'Aromen', $title);
                }

                $description = $seoSettings['description'] ?? null;
                if ($description !== null) {
                    $description = str_replace(['##supplier##', '##brand##', '##common_name##', '##flavor##'],
                        [$supplier, $brand, $commonName, $appendix], $description);
                }

                $keywords = $seoSettings['keywords'] ?? null;
                if ($keywords !== null) {
                    $keywords = str_replace(['##supplier##', '##brand##', '##common_name##', '##flavor##'],
                        [$supplier, $brand, $commonName, $appendix], $keywords);
                }

                $h1 = $seoSettings['h1'];
                if ($h1 !== null) {
                    $h1 = mb_strtoupper(str_replace(['##supplier##', '##brand##', '##common_name##', '##flavor##'],
                        [$supplier, $brand, $commonName, $appendix], $h1));
                }
                $category = $categoryRepository->findOneBy(['path' => $idx]) ?? new Category();
                $category->setPath($idx);
                $category->setDescription($description);
                $category->setTitle($title);
                $category->setKeywords($keywords);
                $category->setH1($h1);
                $this->modelManager->persist($category);
                $this->categorySeoItems[$idx] = $category;
            }
        }
        return;
    }

    protected function getBasePathSeoItems(string $path)
    {
        if (isset($this->categorySeoItems[$path])) return;

        $map = $this->classConfig['category_seo_items'];
        $categoryRepository = $this->getCategoryRepository();
        $pathItems = $pathItems = array_map('trim', explode('>', $path));
        $idx = null;
        foreach ($pathItems as $item) {
            $idx = $idx ? $idx . ' > ' . $item : $item;
            if (isset($this->categorySeoItems[$idx])) continue;
            $items = $map[$idx] ?? null;
            if ($items) {
                $items['path'] = $idx;
                $category = $categoryRepository->findOneBy(['path' => $idx]) ?? new Category();
                $this->modelManager->persist($category);
                $category->fromArray($items);
                $this->categorySeoItems[$idx] = $category;
            }
        }
    }

    protected function getCategoryRepository()
    {
        return $this->categoryRepository ?? $this->categoryRepository = $this->modelManager->getRepository(Category::class);
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
    }
}