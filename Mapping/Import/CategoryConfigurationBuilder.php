<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;

class CategoryConfigurationBuilder implements ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    protected $config;
    protected $typeMap;

    protected $flavorCategories;

    public function buildCategoryConfiguration()
    {
        $this->categoryMap = $this->getCategoryMap();
        $flavorCategories = [];

        $dql = 'SELECT p.flavorCategory, p.type, p.supplier, p.brand, p.commonName FROM ' . Product::class . ' p';
        $records = $this->modelManager->createQuery($dql)->getResult();

        foreach ($records as $record) {
            $fCategories = $record['flavorCategory'];

            if (!empty($fCategories)) {
                $fCategories = array_map('trim', explode(',', $fCategories));
                foreach ($fCategories as $fCategory) {
                    $flavorCategories[$fCategory] = null;
                }
            }
            $type = $record['type'];

        }
    }

    protected function getTypeMap()
    {
        if ($this->typeMap) {
            return $this->typeMap;
        }
        $map = [];
        $config = $this->classConfig['type_category_map'] ?? [];
        foreach ($config as $index => $item) {
            foreach ($item['types'] as $type) {
                $map[$type] = $index;
            }
        }
        $this->typeMap = $map;
        return $map;
    }
}