<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use Shopware\Models\Article\Article;
use Zend\Config\Factory;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class CategoryMapper implements ClassConfigAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ClassConfigAwareTrait;

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
            $this->log->debug('Getting category for article ' . $product->getName());
            $categoryPositions = $this->getCategoryPositions($category);
            $swCategory = $this->categoryTool->getCategoryPath($categoryPositions, $rootCategory);
            $article->addCategory($swCategory);
            $swCategory->setChanged();
        }
    }

    public function createCategories(array $products)
    {
        $map = [];
        foreach ($products as $product) {
            if (! $product->isValid()) continue;
            $categories = explode(MXC_DELIMITER_L1, $product->getCategory());
            foreach ($categories as $category) {
                $map[$category] = true;
            }
        }
    }

    public function createCategoryTree() {
        $pathes = array_keys($this->categoryTree['category_positions']);
        $root = $this->categoryTool->findCategoryPath('Deutsch');
        foreach ($pathes as $path) {
            $this->categoryTool->getCategoryPath($this->getCategoryPositions($path), $root);
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
