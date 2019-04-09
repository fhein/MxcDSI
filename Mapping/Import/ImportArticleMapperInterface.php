<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

interface ImportArticleMapperInterface
{
    public function map(Model $model, Article $article);
}