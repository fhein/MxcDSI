<?php

namespace MxcDropshipIntegrator\Jobs;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Shopware\Models\Category\Category;
use MxcCommons\Toolbox\Config\Config;

/**
 * This job checks all prices against the pricing rules using the PriceEngine
 * and updates the prices accordingly in both products and articles
 */
class PullCategorySeoInformation
{
    private static $log;

    public static function run(?array $options = null)
    {
        $services = MxcDropshipIntegrator::getServices();
        /** @var EntityManager $modelManager */
        $modelManager = $services->get('models');
        self::$log = $services->get('logger');

        $categorySeoInformation = [];
        $repository = $modelManager->getRepository(Category::class);

        $categories = $repository->findAll();

        /** @var Category $category */
        foreach ($categories as $category) {
            $id = $category->getId();
            $path = $category->getPath();
            $path = $id . $path;
            $path = self::getPathString($repository, $path);
            $categorySeoInformation[$path] = [
                'id' => $id,
                'metaTitle' => $category->getMetaTitle(),
                'metaDescription' => $category->getMetaDescription(),
                'metaKeywords' => $category->getMetaKeywords(),
                'header' => $category->getCmsHeadline(),
                'description' => $category->getCmsText(),
            ];
        }
        $filename = __DIR__ . '/../Config/CategorySeoInformation.config.php';
        Config::toFile($filename, $categorySeoInformation);
    }

    protected static function getPathString(EntityRepository $repository, $path)
    {
        $categoryIds = explode('|', $path);
        $categoryIds = array_reverse($categoryIds);
        $path = [];
        foreach ($categoryIds as $categoryId) {
            if (empty($categoryId)) continue;
            /** @var Category $category */
            $category = $repository->find($categoryId);
            $path[] = $category->getName();
        }
        unset($path[0]);
        $path = implode(' > ', $path);
        return $path;
    }




}