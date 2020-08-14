<?php

namespace MxcDropshipIntegrator\Models;

use MxcCommons\Toolbox\Models\BaseEntityRepository;

class CategoryRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'  => 'SELECT c FROM MxcDropshipIntegrator\Models\Category c INDEX BY c.path',
    ];
}
