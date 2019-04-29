<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class CategoryMapper extends BaseImportMapper implements ProductMapperInterface
{
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