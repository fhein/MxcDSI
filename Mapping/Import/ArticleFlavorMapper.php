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
        $flavor = $article->getFlavor();
        if ($flavor !== null) {
            return;
        }

        $flavor = $this->config[$article->getIcNumber()]['flavor'];
        if (is_array($flavor) && !empty($flavor)) {
            $article->setFlavor(implode(', ', $flavor));
        }
    }}