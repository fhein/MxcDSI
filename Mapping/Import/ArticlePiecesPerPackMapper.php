<?php


namespace MxcDropshipInnocigs\Mapping\Import;


use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ArticlePiecesPerPackMapper extends BaseImportMapper implements ArticleMapperInterface
{
    /**
     * If a product in general contains several pieces, i.e. not as an option,
     * the mapped product name contains a substring like (xx Stück pro Packung).
     *
     * This xx number of pieces gets derived here.
     *
     * @param Model $model
     * @param Article $article
     */
    public function map(Model $model, Article $article)
    {
        $name = $article->getName();
        $matches = [];
        $ppp = 1;
        if (preg_match('~\((\d+) Stück~', $name, $matches) === 1) {
            $ppp = $matches[1];
        };
        $article->setPiecesPerPack($ppp);
    }

}