<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping\Shopware;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\Category as CategoryConfiguration;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcDropshipIntegrator\Toolbox\Shopware\CategoryTool;
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
    private $categoryConfiguration;

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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function map(Product $product, bool $replace = true)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (!$article) return;

        if ($replace === true) $article->getCategories()->clear();

        $root = $this->getRootCategory();
        $categories = explode(MxcDropshipIntegrator::MXC_DELIMITER_L1, $product->getCategory());

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
            if ($parent !== null) {
                $this->log->debug('Adding category ' . $parent->getName() . ' to ' . $product->getName());
                $article->addCategory($parent);
            }
            $this->modelManager->flush($parent);
            $this->modelManager->flush($article);
        }
    }

    protected function setCategoryProperties(string $path, Category $swCategory)
    {
        // do this once only
        if (! empty($this->categoryPathes[$path])) return;

        /** @var CategoryConfiguration $seo */
        $seo = $this->getCategoryConfiguration()[$path];
        if (! empty($seo)) {
            $swCategory->setMetaTitle($seo->getTitle());
            $swCategory->setMetaDescription($seo->getDescription());
            $swCategory->setMetaKeywords($seo->getKeywords());
            $swCategory->setCmsHeadline($seo->getH1());
        }
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

    protected function getCategoryConfiguration()
    {
        if ($this->categoryConfiguration !== null) return $this->categoryConfiguration;
        $config = $this->modelManager->getRepository(CategoryConfiguration::class)->getAllIndexed();
        return $this->categoryConfiguration = $config;
    }

}
