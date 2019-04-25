<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use Shopware\Models\Article\Article;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class ArticleCategoryMapper implements ClassConfigAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ClassConfigAwareTrait;

    /** @var CategoryTool $categoryTool */
    protected $categoryTool;

    public function __construct(CategoryTool $categoryTool)
    {
        $this->categoryTool = $categoryTool;
    }

    /**
     * Add Shopware categories provided as a list of '#!#' separated category paths
     * to the Shopware article associated to the given InnoCigs article.
     *
     * @param Product $product
     */
    public function map(Product $product)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (!$article) return;

        $root = $this->classConfig['root_category'] ?? 'Deutsch';
        $rootCategory = $this->categoryTool->getCategoryPath($root);
        $icCategories = explode(MXC_DELIMITER_L1, $product->getCategory());
        foreach ($icCategories as $icCategory) {
            // if ($product->getName() === 'SC - Base - 100 ml, 0 mg/ml') xdebug_break();
            $this->log->debug('Getting category for article ' . $product->getName());
            $category = $this->categoryTool->getCategoryPath($icCategory, $rootCategory);
            $article->addCategory($category);
            $category->setChanged();
        }
    }
}
