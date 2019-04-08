<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ArticleFlavorMapper extends BaseImportMapper implements ArticleMapperInterface
{
    /**
     * Assign the product flavor from article configuration.
     *
     * @param Model $model
     * @param Article $article
     */
    public function map(Model $model, Article $article)
    {
        if ($article->getFlavor() !== null) return;

        $flavor = explode(',', $this->config[$article->getIcNumber()]['flavor']);
        $flavor = array_map('trim', $flavor);
        $flavor = implode(', ', $flavor);
        $article->setFlavor($flavor);
    }}