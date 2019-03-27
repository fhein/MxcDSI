<?php

use MxcDropshipInnocigs\Import\PropertyDerivator;

return [
    'types' => [
        PropertyDerivator::TYPE_UNKNOWN         => 'UNKNOWN',
        PropertyDerivator::TYPE_E_CIGARETTE     => 'E_CIGARETTE',
        PropertyDerivator::TYPE_BOX_MOD         => 'BOX_MOD',
        PropertyDerivator::TYPE_E_PIPE          => 'E_PIPE',
        PropertyDerivator::TYPE_CLEAROMIZER     => 'CLEAROMIZER',
        PropertyDerivator::TYPE_LIQUID          => 'LIQUID',
        PropertyDerivator::TYPE_AROMA           => 'AROMA',
        PropertyDerivator::TYPE_SHAKE_VAPE      => 'SHAKE_VAPE',
        PropertyDerivator::TYPE_HEAD            => 'HEAD',
        PropertyDerivator::TYPE_TANK            => 'TANK',
        PropertyDerivator::TYPE_SEAL            => 'SEAL',
        PropertyDerivator::TYPE_DRIP_TIP        => 'DRIP_TIP',
        PropertyDerivator::TYPE_POD             => 'POD',
        PropertyDerivator::TYPE_CARTRIDGE       => 'CARTRIDGE',
        PropertyDerivator::TYPE_CELL            => 'CELL',
        PropertyDerivator::TYPE_CELL_BOX        => 'CELL_BOX',
        PropertyDerivator::TYPE_BASE            => 'BASE',
        PropertyDerivator::TYPE_CHARGER         => 'CHARGER',
        PropertyDerivator::TYPE_BAG             => 'BAG',
        PropertyDerivator::TYPE_TOOL            => 'TOOL',
        PropertyDerivator::TYPE_WADDING         => 'WADDING', // Watte
        PropertyDerivator::TYPE_WIRE            => 'WIRE',
        PropertyDerivator::TYPE_BOTTLE          => 'BOTTLE',
        PropertyDerivator::TYPE_SQUONKER_BOTTLE => 'SQUONKER_BOTTLE',
        PropertyDerivator::TYPE_VAPORIZER       => 'VAPORIZER',
        PropertyDerivator::TYPE_SHOT            => 'SHOT',
        PropertyDerivator::TYPE_CABLE           => 'CABLE',
        PropertyDerivator::TYPE_BOX_MOD_CELL    => 'BOX_MOD_CELL',
        PropertyDerivator::TYPE_COIL            => 'COIL',
        PropertyDerivator::TYPE_RDA_BASE        => 'RDA_BASE',
        PropertyDerivator::TYPE_MAGNET          => 'MAGNET',
        PropertyDerivator::TYPE_MAGNET_ADAPTOR  => 'MAGNET_ADAPTER',
        PropertyDerivator::TYPE_ACCESSORY       => 'ACCESSORY',
        PropertyDerivator::TYPE_BATTERY_CAP     => 'BATTERY_CAP',
        PropertyDerivator::TYPE_EXTENSION_KIT   => 'EXTENSION_KIT',
        PropertyDerivator::TYPE_CONVERSION_KIT  => 'CONVERSION_KIT',
        PropertyDerivator::TYPE_E_HOOKAH        => 'E_HOOKAH',
        PropertyDerivator::TYPE_SQUONKER_BOX    => 'SQUONKER_BOX',
        PropertyDerivator::TYPE_EMPTY_BOTTLE    => 'EMPTY_BOTTLE',
        PropertyDerivator::TYPE_EASY3_CAP       => 'EASY3_CAP',
        PropertyDerivator::TYPE_DECK            => 'DECK',
        PropertyDerivator::TYPE_HEATING_PLATE   => 'HEATING_PLATE',
        PropertyDerivator::TYPE_DRIP_TIP_CAP    => 'DRIP_TIP_CAP',
        PropertyDerivator::TYPE_TANK_PROTECTION => 'TANK_PROTECTION',
        PropertyDerivator::TYPE_STORAGE         => 'STORAGE',
    ],

    'category_type_map' => [
        'Akkus'                         => PropertyDerivator::TYPE_BOX_MOD,
        'Akkuträger'                    => PropertyDerivator::TYPE_BOX_MOD_CELL,
        'Verdampfer'                    => PropertyDerivator::TYPE_CLEAROMIZER,
        'E-Zigaretten'                  => PropertyDerivator::TYPE_E_CIGARETTE,
        'E-Pfeifen'                     => PropertyDerivator::TYPE_E_PIPE,
        'Vaporizer'                     => PropertyDerivator::TYPE_VAPORIZER,
        'Liquids'                       => PropertyDerivator::TYPE_LIQUID,
        'Aromen'                        => PropertyDerivator::TYPE_AROMA,
        'Shake & Vape'                  => PropertyDerivator::TYPE_SHAKE_VAPE,
        'Zubehör > Verdampferköpfe'     => PropertyDerivator::TYPE_HEAD,
        'Zubehör > Kabel & Stecker'     => PropertyDerivator::TYPE_CABLE,
        'Zubehör > Dichtungen'          => PropertyDerivator::TYPE_SEAL,
        'Zubehör > Taschen'             => PropertyDerivator::TYPE_BAG,
        'Zubehör > Werkzeug'            => PropertyDerivator::TYPE_TOOL,
        'Zubehör > Glastanks'           => PropertyDerivator::TYPE_TANK,
        'Zubehör > Mundstücke & Schutz' => PropertyDerivator::TYPE_DRIP_TIP,
        'Zubehör > Ladegeräte'          => PropertyDerivator::TYPE_CHARGER,
        'Liquids > Easy 3 Caps'         => PropertyDerivator::TYPE_POD,
        'Zubehör > Accessoires'         => PropertyDerivator::TYPE_ACCESSORY,
    ],

    'name_type_map' => [
        '~Akkuzelle~'                          => PropertyDerivator::TYPE_CELL,
        '~Akkubox~'                            => PropertyDerivator::TYPE_CELL_BOX,
        '~Watte~'                              => PropertyDerivator::TYPE_WADDING,
        '~Wickeldraht~'                        => PropertyDerivator::TYPE_WIRE,
        '~Coil~'                               => PropertyDerivator::TYPE_COIL,
        '~Cartridge~'                          => PropertyDerivator::TYPE_CARTRIDGE,
        '~Pod~'                                => PropertyDerivator::TYPE_POD,
        '~Guillotine V2 - Base~'               => PropertyDerivator::TYPE_RDA_BASE,
        '~Easy 3.*kabel~'                      => PropertyDerivator::TYPE_CABLE,
        '~Shot~'                               => PropertyDerivator::TYPE_SHOT,
        '~Leerflasche~'                        => PropertyDerivator::TYPE_BOTTLE,
        '~Base~'                               => PropertyDerivator::TYPE_BASE,
        '~Ersatzmagnet~'                       => PropertyDerivator::TYPE_MAGNET,
        '~Magnet-Adapter~'                     => PropertyDerivator::TYPE_MAGNET_ADAPTOR,
        '~(Batteriekappe)|(Batteriehülse)~'    => PropertyDerivator::TYPE_BATTERY_CAP,
        '~(Squonker Flasche)|(Liquidflasche)~' => PropertyDerivator::TYPE_SQUONKER_BOTTLE,
    ],

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