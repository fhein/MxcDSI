<?php

// use MxcDropshipInnocigs\Models\Product;

return [
    'applyFilters' => false,
    'filters'     => [
        'update'     => [
//            [
//                'entity' => Product::class,
//                'andWhere' => [
//                    [ 'field' => 'name', 'operator' => 'LIKE', 'value' => '%iquid%' ]
//                ],
//                'set' => [ 'accepted' => false, 'active' => false ],
//            ],
//            [
//                'entity' => Product::class,
//                'andWhere' => [
//                    [ 'field' => 'name', 'operator' => 'LIKE', 'value' => '%Aroma%' ]
//                ],
//                'set' => [ 'accepted' => false, 'active' => false, ]
//            ],
//            [
//                'entity' => Product::class,
//                'andWhere' => [
//                    [ 'field' => 'brand', 'operator' => 'LIKE', 'value' => 'DVTCH Amsterdam' ]
//                ],
//                'set'     => [ 'accepted' => false, 'active' => false, ]
//            ],
        ],
    ],
    'modelMapping' => [
        'category' => ['Product','category'],
        'master' => ['Product','icNumber'],
        'model' => ['Variant','icNumber'],
        'ean' => ['Variant','ean'],
        'name' => ['Variant','name'],
        'productName' => ['Variant','name'],
        'unit' => ['Variant','unit'],
        'content' => ['Variant','content'],
        'description' => ['Product','icDescription'],
        'purchasePrice' => ['Variant','purchasePrice'],
        'recommendedRetailPrice' => ['Variant','recommendedRetailPrice'],
        'images' => ['Variant','images'],
        'manufacturer' => ['Product','manufacturer'],
        'manual' => ['Product','manual']
    ]

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