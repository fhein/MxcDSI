<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Mxc\Shopware\Plugin\Service\ServicesTrait;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Category\Category;

class CategoryTool
{
    use ServicesTrait;
    /** @var ModelManager */
    protected $modelManager;

    protected $log;

    public function __construct()
    {
        $this->modelManager = Shopware()->Models();
        $this->log = $this->getServices()->get('logger');
    }

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
}