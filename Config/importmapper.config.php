<?php

// use MxcDropshipInnocigs\Models\Article;

return [
    'applyFilters' => true,
    'filters'     => [
        'update'     => [
//            [
//                'entity' => Article::class,
//                'andWhere' => [
//                    [ 'field' => 'name', 'operator' => 'LIKE', 'value' => '%iquid%' ]
//                ],
//                'set' => [ 'accepted' => false, 'active' => false ],
//            ],
//            [
//                'entity' => Article::class,
//                'andWhere' => [
//                    [ 'field' => 'name', 'operator' => 'LIKE', 'value' => '%Aroma%' ]
//                ],
//                'set' => [ 'accepted' => false, 'active' => false, ]
//            ],
//            [
//                'entity' => Article::class,
//                'andWhere' => [
//                    [ 'field' => 'brand', 'operator' => 'LIKE', 'value' => 'DVTCH Amsterdam' ]
//                ],
//                'set'     => [ 'accepted' => false, 'active' => false, ]
//            ],
        ],
    ],
    'flavors' => include __DIR__ . '/flavor.config.php',
];