<?php

namespace MxcDropshipInnocigs\Models;


class CategoryRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'  => 'SELECT c FROM MxcDropshipInnocigs\Models\Category c INDEX BY c.path',
    ];
}
