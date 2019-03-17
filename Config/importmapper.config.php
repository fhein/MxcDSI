<?php

// use MxcDropshipInnocigs\Models\Article;

return [
    'applyFilters' => false,
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

    // this is an attempt to define rules to set the accepted state on creation
//    'accept_filter' => [
//        Group::class => [
//            'default' => true,
//            'rules' => [
//                ['name'  => ['preg_match' => ['~1er Packung~']], 'set' => ['accepted' => true, 'active' => false]]
//            ]
//        ],
//        Option::class => [
//            'default' => true,
//            'groups' => [
//                'Packungsgröße' => [
//                    'default' => false,
//                    'rules' => [
//                        ['name'  => ['preg_match' => ['~1er Packung~']], 'set' => ['accepted' => true, 'active' => false]]
//                    ]
//                ]
//            ]
//        ]
//    ]
];