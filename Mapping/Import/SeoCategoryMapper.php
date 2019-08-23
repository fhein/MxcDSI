<?php


namespace MxcDropshipInnocigs\Mapping\Import;


use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class SeoCategoryMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var array */
    protected $report = [];

    protected $categoryMap;
    protected $classConfigFile = __DIR__ . '/../../Config/CategoryMapper.config.php';

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
        }
    }

    public function remap(Product $product)
    {
        $type = $product->getType();
        $categoryMap = $this->getCategoryMap();

        $category = $categoryMap[$type]['base_category'] ?? null;
        $seoIndex = $categoryMap[$type]['append_subcategory'] ?? 'base';
        $seoSettings = $categoryMap[$type]['seo'][$seoIndex];
        $categories = [];

        $flavorCategories = $product->getFlavorCategory();
        if (! empty($flavorCategories)) {
            $flavorCategories = array_map('trim', explode(',', $flavorCategories));
            foreach ($flavorCategories as $flavorCategory) {
                // @todo:
            }
        }

        $addlCategories = $product->getAddlCategory();
        if (! empty($addlCategories)) {
            $addlCategories = array_map('trim', explode(',', $addlCategories));
            foreach ($addlCategories as $addlCategory) {
                // @todo
            }
        }

        $appendSubcategory = $categoryMap[$type]['append_subcategory'] ?? null;
        if (is_string($appendSubcategory)) {
            switch ($appendSubcategory) {
                case 'supplier':
                    $appendSubcategory = $product->getSupplier();
                    if ($appendSubcategory === 'InnoCigs') {
                        $appendSubcategory = $product->getBrand();
                    }
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
        }
        $title = null;
        $description = null;
        if ($seoSettings !== null) {
            $supplier = $product->getSupplier();
            if ($supplier === 'InnoCigs') $supplier = $product->getBrand();
            $brand = $product->getBrand();
            $commonName = $product->getCommonName();

            $title = $seoSettings['title'] ?? null;
            if ($title !== null) {
                $title = str_replace(['##supplier##', '##brand##', '##common_name##'], [$supplier, $brand, $commonName], $title);
            }
            $description = $seoSettings['description'] ?? null;
            if ($description !== null) {
                $description = str_replace(['##supplier##', '##brand##', '##common_name##'], [$supplier, $brand, $commonName], $description);
            }

            $keywords = $seoSettings['keywords'] ?? null;
            if ($keywords !== null) {
                $keywords = str_replace(['##supplier##', '##brand##', '##common_name##'], [$supplier, $brand, $commonName], $keywords);
            }
        }
        $category = $appendSubcategory ? $category . ' > ' . $appendSubcategory : $category;
        $this->report[$category] = [
            'seo_title' => $title ?? 'NULL',
            'seo_description' => $description ?? 'NULL',
            'seo_keywords' => $keywords ?? 'NULL',
            'seo_t_length' => $title ? strlen($title) : 0,
            'seo_d_length' => $description ? strlen($description) : 0,
        ];
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
                $map[$type]['seo'] = $item['seo'];
            }
        }
        $this->categoryMap = $map;
        return $map;
    }

    public function report()
    {
        $this->log->debug(var_export($this->report, true));
    }
}