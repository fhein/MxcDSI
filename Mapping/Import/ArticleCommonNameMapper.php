<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ArticleCommonNameMapper extends BaseImportMapper implements ArticleMapperInterface
{

    /**
     * The common name of an article is the pure product name without
     * supplier, article group and without any other info.
     *
     * The common name gets determined here and is utilized to identify
     * related products.
     *
     * @param Model $model
     * @param Article $article
     */
    public function map(Model $model, Article $article)
    {
        $name = $article->getName();
        $raw = explode(' - ', $name);
        $index = $this->config['common_name_index'][$raw[0]][$raw[1]] ?? 1;
        $name = trim($raw[$index] ?? $raw[0]);
        $replacements = ['~ \(\d+ Stück pro Packung\)~', '~Head$~'];
        $name = preg_replace($replacements, '', $name);
        $article->setCommonName(trim($name));
    }
}