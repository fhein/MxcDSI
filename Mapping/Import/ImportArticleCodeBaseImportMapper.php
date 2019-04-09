<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ImportArticleCodeBaseImportMapper extends BaseImportMapper implements ImportArticleMapperInterface
{
    /**
     * Map an InnoCigs article code.
     *
     * @param Model $model
     * @param Article $article
     */
    public function map(Model $model, Article $article): void
    {
        $number = $model->getMaster();
        $article->setNumber($this->config['article_codes'][$number] ?? $number);
    }
}

