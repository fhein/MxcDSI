<?php

return [

    'common_name_index' => [
        'Twelve Monkeys' => [
            'Origins' => 2,
        ],
        'Twisted'        => [
            'Cryostasis'                           => 2,
            'Highway Vapor'                        => 2,
            'John Smith\'s Blended Tobacco Flavor' => 2,
            'Mr. Bubbles'                          => 2,
            'Timelord'                             => 2,
            'Truckin Vaporz'                       => 2,
            'Peng Juice Aroma'                     => 2,
        ],
        'Vampire Vape'   => [
            'Koncept XIX' => 2,
            'Shortz'      => 2,
            'VLADS VG'    => 2,
        ],
        'I VG'           => [
            'Custards' => 2,
            'Deserts'  => 2,
        ],
    ],

    'recommended_dosage' => [
        'Vampire Vape' => '10-15',
    ],

    'related_article_groups' => [
        'AROMA'        => [
            'match_common_name' => false,
            'groups'            => [
                'BASE',
                'SHOT'
            ],
        ],
        'BASE'         => [
            'match_common_name' => false,
            'groups'            => [
                'SHOT'
            ],
        ],
        'SHAKE_VAPE'   => [
            'match_common_name' => false,
            'groups'            => [
                'SHOT'
            ],
        ],
        'CELL'         => [
            'match_common_name' => false,
            'groups'            => [
                'CELL_BOX',
                'CHARGER',
            ],
        ],
        'CLEAROMIZER'  => [
            'match_common_name' => true,
            'groups'            => [
                'DRIP_TIP',
                'TANK',
                'TANK_PROTECTION',
                'HEAD',
                'SEAL',
                'RDA_BASE',
                'ACCESSORY',
                'BOX_MOD',
                'BOX_MOD_CELL',
            ],
        ],
        'BOX_MOD'      => [
            'match_common_name' => true,
            'groups'            => [
                'SQUONKER_BOTTLE',
                'ACCESSORY',
                'CABLE',
                'CLEROMIZER'
            ],
        ],
        'BOX_MOD_CELL' => [
            'match_common_name' => true,
            'groups'            => [
                'SQUONKER_BOTTLE',
                'ACCESSORY',
                'CABLE',
                'CLEAROMIZER'
            ],
        ],
        'E_CIGARETTE'  => [
            'match_common_name' => true,
            'groups'            => [
                'POD',
                'CARTRIDGE',
                'LIQUID',
                'ACCESSORY',
                'SQUONKER_BOTTLE',
                'CABLE',
                'DRIP_TIP',
                'BOX_MOD',
                'BOX_MOD_CELL',
                'HEAD',
                'CLEAROMIZER',
                'TANK',
                'TANK_PROTECTION',
                'SEAL',
                'CABLE'
            ],
        ],
        'E_PIPE'       => [
            'match_common_name' => true,
            'groups'            => [
                'POD',
                'CARTRIDGE',
                'LIQUID',
                'ACCESSORY',
                'SQUONKER_BOTTLE',
                'CABLE',
                'DRIP_TIP',
                'BOX_MOD',
                'BOX_MOD_CELL',
                'HEAD',
                'CLEAROMIZER',
                'TANK',
                'TANK_PROTECTION',
                'SEAL',
                'CABLE',
            ],
        ],
        'VAPORIZER'    => [
            'match_common_name' => true,
            'groups'            => [
                'POD',
                'CARTRIDGE',
                'LIQUID',
                'ACCESSORY',
                'SQUONKER_BOTTLE',
                'CABLE',
                'DRIP_TIP',
                'BOX_MOD',
                'BOX_MOD_CELL',
                'HEAD',
                'CLEAROMIZER',
                'TANK',
                'TANK_PROTECTION',
                'SEAL',
                'CABLE',
            ],
        ],
    ],
    'similar_article_groups' => [
        'AROMA'      => [
            'match_common_name' => true,
            'groups'            => [
                'LQIUID',
                'SHAKE_VAPE'
            ],
        ],
        'LIQUID'     => [
            'match_common_name' => true,
            'groups'            => [
                'AROMA',
                'SHAKE_VAPE'
            ],
        ],
        'SHAKE_VAPE' => [
            'match_common_name' => true,
            'groups'            => [
                'AROMA',
                'LIQUID'
            ],
        ],
    ],
    // We assume all articles having one of these flavor components in common as similar
    'similar_flavors'        => [
        0  => 'Apfelstrudel',
        1  => 'Baklava',
        2  => 'Bienenstich',
        3  => 'Biskuit',
        4  => 'Blätterteig',
        5  => 'Churro',
        6  => 'Crumble',
        7  => 'Donut',
        8  => 'Keks',
        9  => 'Kuchen',
        10 => 'Käsekuchen',
        11 => 'Macaron',
        12 => 'Muffin',
        13 => 'Müsliriegel',
        14 => 'Pfannkuchen',
        15 => 'Salzbretzel',
        16 => 'Streuselkuchen',
        17 => 'Toast',
        18 => 'Torte',
        19 => 'Waffel',
        20 => 'Energy Drink',
        21 => 'Limonade',
        22 => 'Mojito',
        23 => 'Cola',
    ]
];