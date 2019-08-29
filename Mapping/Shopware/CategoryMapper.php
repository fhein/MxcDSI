<?php /** @noinspection PhpUnhandledExceptionInspection */

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
use Shopware\Models\Category\Category;
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

    private $categoryPathes;

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
        $root = $this->categoryTool->findCategoryPath($root);
        $categories = explode(MXC_DELIMITER_L1, $product->getCategory());

        foreach ($categories as $category) {
            $path = array_map('trim', explode('>', $category));
            $idx = '';
            $parent = $root;
            foreach ($path as $name) {
                $idx = $idx === '' ? $name : $idx . ' > ' . $name;
                $parent = $this->categoryTool->getChildCategory($parent, $name, true);
                if ($parent === null) break;
                $this->setCategoryProperties($idx, $parent);
                $parent->setChanged();
            }
            if ($parent !== null) $article->addCategory($parent);
            $this->modelManager->flush();
        }
    }

    protected function setCategoryProperties(string $path, Category $swCategory)
    {
        if (! empty($this->categoryPathes[$path])) return;
        $seo = $this->categoryTree['category_seo_items'][$path] ?? [];
        $swCategory->setHideFilter(true);

        if (! empty($seo)) {
            $swCategory->setMetaTitle($seo['seo_title']);
            $swCategory->setMetaDescription($seo['seo_description']);
            $swCategory->setMetaKeywords($seo['seo_keywords']);
            $swCategory->setCmsHeadline($seo['seo_h1']);
        }
        $this->categoryPathes[$path] = true;
    }

    public function rebuildCategorySeoInformation() {
        $root = $this->classConfig['root_category'] ?? 'Deutsch';
        $rootCategory = $this->categoryTool->findCategoryPath($root, null);
        $pathes = array_keys($this->categoryTree['category_seo_items']);
        foreach ($pathes as $path) {
            $swCategory = $this->categoryTool->findCategoryPath($path, $rootCategory);
            if ($swCategory === null) continue;
            $this->setCategoryProperties($path, $swCategory);
        }
    }
}
