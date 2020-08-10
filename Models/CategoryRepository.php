<?php

namespace MxcDropshipIntegrator\Models;


class CategoryRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'  => 'SELECT c FROM MxcDropshipIntegrator\Models\Category c INDEX BY c.path',
    ];
}
