<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use Shopware\Models\Article\Article as ShopwareArticle;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;

class ShopwareCategoryMapper
{
    /** @var LoggerInterface $log */
    protected $log;

    /** @var array  */
    protected $config;

    /** @var CategoryTool $categoryTool */
    protected $categoryTool;

    public function __construct(CategoryTool $categoryTool, array $config, LoggerInterface $log)
    {
        $this->log = $log;
        $this->config = $config;
        $this->categoryTool = $categoryTool;
    }

    /**
     * Add Shopware categories provided as a list of '#!#' separated category paths
     * to the Shopware article associated to the given InnoCigs article.
     *
     * @param Article $icArticle
     */
    public function map(Article $icArticle)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (!$swArticle) {
            return;
        }

        $root = $this->config['root_category'] ?? 'Deutsch';
        $rootCategory = $this->categoryTool->getCategoryPath($root);
        $catgories = explode(MXC_DELIMITER_L1, $icArticle->getCategory());
        foreach ($catgories as $category) {
            // if ($icArticle->getName() === 'SC - Base - 100 ml, 0 mg/ml') xdebug_break();
            $this->log->debug('Getting category for article ' . $icArticle->getName());
            $swCategory = $this->categoryTool->getCategoryPath($category, $rootCategory);
            $swArticle->addCategory($swCategory);
            $swCategory->setChanged();
        }
    }}