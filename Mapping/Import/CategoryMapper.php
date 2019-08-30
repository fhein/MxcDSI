<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Report\ArrayReport;
use Psr\Log\LoggerAwareTrait;
use Zend\Config\Factory;

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
            // remove empty entries
            $categories = array_filter(array_map('trim', $categories));
            if (! empty($categories)) {
                $category = implode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $categories);
                $this->report[$category][] = $product->getName();
            }
        }
        $product->setCategory($category);
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
            $h1 = mb_strtoupper(str_replace('##flavor##', $flavor, $profile['h1']));
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
        if (! empty($this->categorySeoItems[$path])) return;

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

        $idx = null;
        foreach ($pathItems as $item) {
            $idx = $idx === null ? $item : $idx . ' > ' . $item;
            if (strlen($idx) === $pathLen) {
                $seoIndex = $map[$this->typeMap[$type]]['append'] ?? $item;
            } else {
                $seoIndex = $item;
            }
            $seoSettings = $map[$this->typeMap[$type]]['seo'][$seoIndex];

            if (empty($seoSettings)) return;
            if (! empty($this->categorySeoItems[$idx])) continue;

            $title = $seoSettings['title'] ?? null;
            if ($title !== null) {
                $title = str_replace(['##supplier##', '##brand##', '##common_name##'], [$supplier, $brand, $commonName], $title);
                //--- workaround for Elli's Aromen
                $title = str_replace ('Aromen Aromen', 'Aromen', $title);
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
                $h1 = mb_strtoupper(str_replace(['##supplier##', '##brand##', '##common_name##'],
                    [$supplier, $brand, $commonName], $h1));
            }
            $this->categorySeoItems[$idx] = [
                'seo_title'       => $title,
                'seo_description' => $description,
                'seo_keywords'    => $keywords,
                'seo_h1'          => $h1,
            ];
        }
    }
}