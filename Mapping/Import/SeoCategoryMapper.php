<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;

class SeoCategoryMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var array */
    protected $report = [];

    protected $typeMap;

    protected $seoItems = [];

    protected $categoryMap;

    protected $config;

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
        $typeMap = $this->getTypeMap();
        $map = $this->classConfig['type_category_map'];

        $category = $map[$typeMap[$type]]['path'] ?? null;
        $seoIndex = $map[$typeMap[$type]]['append'] ?? 'base';
        $seoSettings = $map[$typeMap[$type]]['seo'][$seoIndex];


        $subCategory = $map[$typeMap[$type]]['append'] ?? null;
        if (is_string($subCategory)) {
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
        $category = $subCategory ? $category . ' > ' . $subCategory : $category;
        $this->report[$category] = [
            'seo_title' => $title ?? 'NULL',
            'seo_description' => $description ?? 'NULL',
            'seo_keywords' => $keywords ?? 'NULL',
        ];
    }

    protected function getTypeMap() {
        $typeMap = [];
        foreach ($this->classConfig['type_category_map'] as $idx => $record) {
            foreach ($record['types'] as $type) {
                $typeMap[$type] = $idx;
            }
        }
        return $typeMap;
    }

    public function report()
    {
        $report = new ArrayReport();
        $report([ 'seoCategories' => $this->report]);
    }
}