<?php

use MxcDropshipInnocigs\Models\Article;

return [
    'category_type_map' => [
        'Akkus'                         => Article::TYPE_BOX_MOD,
        'Akkuträger'                    => Article::TYPE_BOX_MOD_CELL,
        'Verdampfer'                    => Article::TYPE_CLEAROMIZER,
        'E-Zigaretten'                  => Article::TYPE_E_CIGARETTE,
        'E-Pfeifen'                     => Article::TYPE_E_PIPE,
        'Vaporizer'                     => Article::TYPE_VAPORIZER,
        'Liquids'                       => Article::TYPE_LIQUID,
        'Aromen'                        => Article::TYPE_AROMA,
        'Shake & Vape'                  => Article::TYPE_SHAKE_VAPE,
        'Zubehör > Verdampferköpfe'     => Article::TYPE_HEAD,
        'Zubehör > Kabel & Stecker'     => Article::TYPE_CABLE,
        'Zubehör > Dichtungen'          => Article::TYPE_SEAL,
        'Zubehör > Taschen'             => Article::TYPE_BAG,
        'Zubehör > Werkzeug'            => Article::TYPE_TOOL,
        'Zubehör > Glastanks'           => Article::TYPE_TANK,
        'Zubehör > Mundstücke & Schutz' => Article::TYPE_DRIP_TIP,
        'Zubehör > Ladegeräte'          => Article::TYPE_CHARGER,
        'Liquids > Easy 3 Caps'         => Article::TYPE_POD,
        'Zubehör > Accessoires'         => Article::TYPE_ACCESSORY,
    ],

    'name_type_map' => [
        '~Akkuzelle~'                          => Article::TYPE_CELL,
        '~Akkubox~'                            => Article::TYPE_CELL_BOX,
        '~Watte~'                              => Article::TYPE_WADDING,
        '~Wickeldraht~'                        => Article::TYPE_WIRE,
        '~Coil~'                               => Article::TYPE_COIL,
        '~Cartridge~'                          => Article::TYPE_CARTRIDGE,
        '~Pod~'                                => Article::TYPE_POD,
        '~Guillotine V2 - Base~'               => Article::TYPE_RDA_BASE,
        '~Shot~'                               => Article::TYPE_SHOT,
        '~Leerflasche~'                        => Article::TYPE_BOTTLE,
        '~Base~'                               => Article::TYPE_BASE,
        '~Ersatzmagnet~'                       => Article::TYPE_MAGNET,
        '~Magnet-Adapter~'                     => Article::TYPE_MAGNET_ADAPTER,
        '~(Batteriekappe)|(Batteriehülse)~'    => Article::TYPE_BATTERY_CAP,
        '~(Squonker Flasche)|(Liquidflasche)~' => Article::TYPE_SQUONKER_BOTTLE,
    ],

    'name_index' => [
        'Twelve Monkeys' => [
            'Origins' => 2,
        ],
        'Twisted' => [
            'Cryostasis' => 2,
            'Highway Vapor' => 2,
            'John Smith\'s Blended Tobacco Flavor' => 2,
            'Mr. Bubbles' => 2,
            'Timelord' => 2,
            'Truckin Vaporz' => 2,
        ],
        'Vampire Vape' => [
            'Koncept XIX' => 2,
            'Shortz' => 2,
            'VLADS VG' => 2,
        ],
        'I VG' => [
            'Custards' => 2,
            'Deserts'  => 2,
        ],
    ],

    'recommended_dosage' => [
        'Vampire Vape' => [
            'min' => 10,
            'max' => 15,
        ]
    ]
];