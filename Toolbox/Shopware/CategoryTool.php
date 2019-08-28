<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Models\Article\Article;
use Shopware\Models\Category\Category;

class CategoryTool implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    protected $childrenQuery;
    protected $categoryCache;

    public function removeEmptyCategories()
    {
        $dql = 'SELECT c FROM Shopware\Models\Category\Category c WHERE c.parentId IS NOT null AND c.blog = 0 '
            . 'AND c.articles IS EMPTY AND c.children IS EMPTY AND c.name <> \'Deutsch\'';
        $query = $this->modelManager->createQuery($dql);
        $count = 0;
        while (true) {
            $emptyCategories = $query->getResult();
            if (empty($emptyCategories)) return $count;
            /** @var Category $category */
            foreach ($emptyCategories as $category) {
                $count++;
                $this->modelManager->remove($category);
                $this->log->debug('Empty category: ' . $category->getName());
            }
            $this->modelManager->flush();
            $this->modelManager->clear();
        }
        return $count;
    }

    public function findCategoryPath(string $path, Category $root = null, bool $create = true)
    {
        $repository = $this->modelManager->getRepository(Category::class);
        /** @var Category $parent */
        $parent = $root ?? $repository->findOneBy(['parentId' => null]);
        $nodes = array_map('trim', explode('>', $path));
        foreach ($nodes as $name) {
            $child = $repository->findOneBy(['name' => $name, 'parentId' => $parent->getId()]);
            if (! empty($child)) {
                $parent = $child;
                continue;
            }
            if ($create === true) {
                $parent = $this->createCategory($parent, $name);
            } else {
                $parent = null;
                break;
            }
        }
        return $parent;
    }

    /**
     * Get a Shopware category object for a given category path array. The array holds a list of
     * node => position entries.
     * All categories of the path are created if they do not exist. The category path gets created
     * below a given root category. If no root category is provided, the path will be added below
     * the Shopware root category.
     *
     * @param array $path
     * @param Category|null $root
     * @param bool $create
     * @return Category
     */
    public function getCategoryPath(array $path, Category $root = null, bool $create = true)
    {
        $repository = $this->modelManager->getRepository(Category::class);
        /** @var Category $parent */
        $parent = $root ?? $repository->findOneBy(['parentId' => null]);
        foreach ($path as $name => $position) {
            $child = $repository->findOneBy(['name' => $name, 'parentId' => $parent->getId()]);
            if ($child === null) {
                if ($create === true) {
                    $parent = $this->createCategory($parent, $name);
                } else {
                    $parent = null;
                    break;
                }
            }
            $parent->setPosition($position);
        }
        return $parent;
    }

    /**
     * Create and return a new sub-category for a given Shopware category.
     *
     * @param Category $parent
     * @param string $name
     * @return Category
     */
    public function createCategory(Category $parent, string $name)
    {
        $child = new Category();
        $this->modelManager->persist($child);
        $child->setHideFilter(true);
        $child->setName($name);
        $child->setParent($parent);
        $child->setChanged();
        if ($parent->getArticles()->count() > 0) {
            /** @var Article $article */
            foreach ($parent->getArticles() as $article) {
                $article->removeCategory($parent);
                $article->addCategory($child);
            }
            $parent->setChanged();
        }
        $this->modelManager->flush($child);
        return $child;
    }

}