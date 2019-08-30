<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;

class CategoryMapper implements ClassConfigAwareInterface, LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;

    /** @var CategoryTool $categoryTool */
    protected $categoryTool;
    private $categoryPathes;

    public function __construct(CategoryTool $categoryTool)
    {
        $this->categoryTool = $categoryTool;
    }

    /**
     * Add Shopware categories provided as a list of '#!#' separated category paths
     * to the Shopware article associated to the given InnoCigs article.
     *
     * @param Product $product
     * @param bool $replace
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function map(Product $product, bool $replace = true)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (!$article) return;

        if ($replace === true) $article->getCategories()->clear();

        $root = $this->getRootCategory();
        $categories = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $product->getCategory());

        foreach ($categories as $category) {
            $path = array_map('trim', explode('>', $category));
            $idx = '';
            $parent = $root;
            foreach ($path as $name) {
                $parent = $this->categoryTool->getChildCategory($parent, $name, true);
                if ($parent === null) break;
                $idx = $idx === '' ? $name : $idx . ' > ' . $name;
                $this->setCategoryProperties($idx, $parent);
                $parent->setChanged();
            }

            if ($parent !== null) $article->addCategory($parent);
            $this->modelManager->flush();
        }
    }

    protected function setCategoryProperties(string $path, Category $swCategory)
    {
        $seo = $this->classConfig['category_seo_items'][$path] ?? [];
        if (! empty($this->categoryPathes[$path]) || empty($seo)) return;

        $swCategory->setMetaTitle($seo['seo_title']);
        $swCategory->setMetaDescription($seo['seo_description']);
        $swCategory->setMetaKeywords($seo['seo_keywords']);
        $swCategory->setCmsHeadline($seo['seo_h1']);
        $this->categoryPathes[$path] = true;
    }

    public function rebuildCategorySeoInformation() {
        $root = $this->getRootCategory();
        $repository = $this->modelManager->getRepository(Category::class);
        $pathes = $this->categoryTool->getChildPathes($root);
        foreach ($pathes as $idx => $path) {
            /** @var Category $swCategory */
            $swCategory = $repository->find($idx);
            if ($swCategory === null) continue;
            $this->setCategoryProperties($path, $swCategory);
        }
    }

    public function removeEmptyProductCategories() {
        $root = $this->getRootCategory();
        $productCategories = $this->classConfig['product_categories'];
        $count = 0;
        foreach ($productCategories as $category) {
            $category  = $this->categoryTool->findCategoryPath($category, $root);
            if ($category === null) continue;
            $count += $this->categoryTool->removeEmptyProductCategories($category);
        }
        return $count;
    }

    protected function getRootCategory()
    {
        $root = $this->classConfig['root_category'] ?? 'Deutsch';
        return $this->categoryTool->findCategoryPath($root, null);
    }

}
