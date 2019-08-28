<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use Shopware\Models\Article\Article;
use Zend\Config\Factory;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class CategoryMapper implements ClassConfigAwareInterface, LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;

    /** @var CategoryTool $categoryTool */
    protected $categoryTool;

    protected $categoryTreeFile = __DIR__ . '/../../Config/CategoryMapper.config.php';
    protected $categoryTree;

    public function __construct(CategoryTool $categoryTool)
    {
        $this->categoryTool = $categoryTool;
        $this->categoryTree = file_exists($this->categoryTreeFile) ? Factory::fromFile($this->categoryTreeFile) : [];
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
        $rootCategory = $this->categoryTool->findCategoryPath($root);
        $categories = explode(MXC_DELIMITER_L1, $product->getCategory());
        foreach ($categories as $category) {
            $categoryPositions = $this->getCategoryPositions($category);
            $swCategory = $this->categoryTool->getCategoryPath($categoryPositions, $rootCategory);
            $article->addCategory($swCategory);
            $swCategory->setChanged();
        }
    }

    public function setCategorySeoInformation() {
        $root = $this->classConfig['root_category'] ?? 'Deutsch';
        $pathes = $this->categoryTree['category_positions'];
        $seo = $this->categoryTree['category_seo_items'];
        $rootCategory = $this->categoryTool->findCategoryPath($root, null, false);
        foreach ($pathes as $path => $position) {
            $swCategory = $this->categoryTool->findCategoryPath($path, $rootCategory, false);
            if ($swCategory === null) continue;
            if (empty($seo[$path])) continue;
            $swCategory->setMetaTitle($seo[$path]['seo_title']);
            $swCategory->setMetaDescription($seo[$path]['seo_description']);
            $swCategory->setMetaKeywords($seo[$path]['seo_keywords']);
            $swCategory->setCmsHeadline($seo[$path]['seo_h1']);
        }
    }

    protected function getCategoryPositions(string $category) {
        $nodes = array_map('trim', explode('>', $category));
        $idx = null;
        $path = [];
        foreach ($nodes as $node) {
            $idx = $idx ? $idx . ' > ' . $node : $node;
            $path[$node] = $this->categoryTree['category_positions'][$idx] ?? 1;
        }
        return $path;
    }
}
