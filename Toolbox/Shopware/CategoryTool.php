<?php

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

    protected function removeEmptyCategoriesRecursive(Category $root)
    {
        $dql = 'SELECT c FROM Shopware\Models\Category\Category c WHERE c.parentId IS NOT null AND c.blog = 0 AND c.articles IS EMPTY AND c.children IS EMPTY';
        $emptyCategories = $this->modelManager->createQuery($dql)->getResult();
        $done = empty($emptyCategories);
        /** @var Category $category */
        foreach ($emptyCategories as $category) {
            $this->modelManager->remove($category);
            $this->log->debug('Empty category: ' . $category->getName());
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
        $this->modelManager->clear();
        if (! $done) $this->removeEmptyCategoriesRecursive($root);
    }

    public function removeEmptyCategories(Category $root = null)
    {
        $parentId = $root ? $root->getId() : null;
        $root = $root ?? $this->modelManager->getRepository(Category::class)->findOneBy(['parentId' => $parentId]);
        $this->removeEmptyCategoriesRecursive($root);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    /**
     * Get a Shopware category object for a given category path (example: E-Zigaretten > Aspire)
     * All categories of the path are created if they do not exist. The category path gets created
     * below a given root category. If no root category is provided, the path will be added below
     * the Shopware root category.
     *
     * @param string $path
     * @param Category|null $root
     * @return Category
     */
    public function getCategoryPath(string $path, Category $root = null)
    {
        $repository = $this->modelManager->getRepository(Category::class);
        /** @var Category $parent */
        $parent = ($root !== null) ? $root : $repository->findOneBy(['parentId' => null]);
        $nodes = explode(' > ', $path);
        foreach ($nodes as $categoryName) {
            $child = $repository->findOneBy(['name' => $categoryName, 'parentId' => $parent->getId()]);
            $parent = $child ?? $this->createCategory($parent, $categoryName);
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
        return $child;
    }
}