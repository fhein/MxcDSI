<?php


namespace MxcDropshipInnocigs\Mapping\Import;


use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class CategoryMapper extends BaseImportMapper implements ProductMapperInterface
{
    /**
     * Map an article to a category.
     *
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product): void
    {
        $category = null;

        foreach ($this->config['categories'] as $key => $settings) {
            if ($key === 'category') {
                $input = $model->getCategory();
            }
            /** @noinspection PhpUndefinedVariableInspection */
            if (null === $input) {
                $method = 'get' . ucFirst($key);
                if (method_exists($product, $method)) {
                    $input = $product->$method();
                }
            }
            foreach ($settings as $matcher => $mappings) {
                foreach ($mappings as $pattern => $mappedCategory) {
                    if (preg_match('~Easy 3~', $product->getName())) {
                        $supplierTag = $product->getBrand();
                    } else {
                        $supplierTag = preg_match('~(Liquid)|(Aromen)|(Basen)|(Shake \& Vape)~',
                            $mappedCategory) === 1 ? $product->getBrand() : $product->getSupplier();
                    }
                    if ($matcher($pattern, $input) === 1) {
                        $category = $this->addSubCategory($mappedCategory, $supplierTag);
                        $category = preg_replace('~(Easy 3( Caps)?) > (.*)~', '$3 > $1', $category);
                        break 3;
                    }
                }
            }
        }
        if (!$category) {
            $category = '';
        }
        $this->report['category'][$category][$product->getName()] = true;
        $product->setCategory($category);
    }

    public function addSubCategory(string $name, ?string $subcategory)
    {
        if ($subcategory !== null && $subcategory !== '') {
            $name .= ' > ' . $subcategory;
        }
        return $name;
    }
}