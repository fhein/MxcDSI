<?php

return [
    'recommended_dosage' => [
        'Vampire Vape' => '10-15',
    ],

    'related_product_groups' => [
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
                'WADDING',
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
                'WADDING',
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
                'WADDING',
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
                'WADDING',
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
    'similar_product_groups' => [
        'AROMA'      => [
            'match_common_name' => true,
            'groups'            => [
                'LQIUID',
                'SHAKE_VAPE',
                'NICSALT_LIQUID',
            ],
        ],
        'LIQUID'     => [
            'match_common_name' => true,
            'groups'            => [
                'AROMA',
                'SHAKE_VAPE',
                'NICSALT_LIQUID',
            ],
        ],
        'SHAKE_VAPE' => [
            'match_common_name' => true,
            'groups'            => [
                'AROMA',
                'LIQUID',
                'NICSALT_LIQUID',
            ],
        ],
        'NICSALT_LIQUID' => [
            'match_common_name' => true,
            'groups'            => [
                'AROMA',
                'LIQUID',
                'SHAKE_VAPE',
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