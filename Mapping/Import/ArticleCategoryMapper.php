<?php


namespace MxcDropshipInnocigs\Mapping\Import;


use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ArticleCategoryMapper extends BaseImportMapper implements ArticleMapperInterface
{
    /**
     * Map an article to a category.
     *
     * @param Model $model
     * @param Article $article
     */
    public function map(Model $model, Article $article): void
    {
        $category = null;

        foreach ($this->config['categories'] as $key => $settings) {
            if ($key === 'category') {
                $input = $model->getCategory();
            }
            /** @noinspection PhpUndefinedVariableInspection */
            if (null === $input) {
                $method = 'get' . ucFirst($key);
                if (method_exists($article, $method)) {
                    $input = $article->$method();
                }
            }
            foreach ($settings as $matcher => $mappings) {
                foreach ($mappings as $pattern => $mappedCategory) {
                    if (preg_match('~Easy 3~', $article->getName())) {
                        $supplierTag = $article->getBrand();
                    } else {
                        $supplierTag = preg_match('~(Liquid)|(Aromen)|(Basen)|(Shake \& Vape)~',
                            $mappedCategory) === 1 ? $article->getBrand() : $article->getSupplier();
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
        $this->report['category'][$category][$article->getName()] = true;
        $article->setCategory($category);
    }

    public function addSubCategory(string $name, ?string $subcategory)
    {
        if ($subcategory !== null && $subcategory !== '') {
            $name .= ' > ' . $subcategory;
        }
        return $name;
    }
}