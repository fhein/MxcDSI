<?php

return [
    'sizes' => [
        'urlMin' => 30,
        'urlMax' => 42,
        'titleMin' => 35,
        'titleMax' => 45,
        'descriptionMin' => 135,
        'descriptionMax' => 155,
    ],
    'patterns' => [
        [
            'types' => [
                'default'
            ],
            'title' => [
                '~\(\d+ Stück pro Packung\)~' => '',
                '~- by .*$~' => '',
                '~- Candy Mints ~'  => '',
                '~- Polar Ice Vapes ~' => '',
                '~- Panther Series ~' => '',
                '~- John Smith\'s Blended Tobacco Flavor ~' => '',
                '~- ClouDrippin’ Psychedelic Edition ~' => '',
                '~Murata / Sony~' => '',
                '~(GeekVape.*) 28GA \(KA1\) \*2 \+32GA \(Ni80\)( - 0,9 Ohm)~' => '$1$2',
                '~(GeekVape.*) 26GA\*16GA\+30GA( - 0,5 Ohm)~' => '$1$2',
                '~(GeekVape.*)Coils und Watte Set(.*)~' => '$1Coils + Watte$2',
                '~(Nikotinsalz Liquid) - \d+ mg/ml~' => '$1',
            ],
            'description' => '»##title##« günstig kaufen ',
            'keywords'    => '',
        ],
        [
            'types' => [
                'SHAKE_VAPE',
            ],
            'title' => [
                '~- \d+ ml, 0 mg/ml~' => '',
                '~- 0 mg/ml~'         => '',
                '~.*- (VLADS VG)~'    => '$1',
            ],
        ],
        [
            'types' => [
                'AROMA',
            ],
            'title' => [
                '~- \d+ ml~' => '',
                '~VH-\d{4} ~' => '',
                '~- Limited Edition - ~' => '',
            ],
        ],
        [
            'types' => [
                'LIQUID',
                'LIQUID_BOX'
            ],
            'title' => [
                '~(Liquid) - \d+ ml~' => 'E-$1',
            ]
        ],
        [
            'types' => [
              'NICSALT_LIQUID'
            ],
            'title' => [
                '~(Nikotinsalz-Liquid) - \d+ mg/ml~' => '$1'
            ]
        ],
        [
            'types' => [
                'EASY3_CAP'
            ],
            'title' => [
                '~(SC) - (Easy 3) - (.*) - (Caps)~' => '$1 - $3 - $4 für die $1 $2'
            ]
        ],
        [
            'types' => [
                'EASY4_CAP'
            ],
            'title' => [
                '~(SC) - (Easy 4) - (.*) - (Caps)~' => '$1 - $3 - $4 für die $1 $2'
            ]
        ],
        [
            'types' => [
                'TOOL',
            ],
            'title' => [
                '~(Flaschenöffner)~' => '$1 für Chubby Gorilla',
            ],
        ],
        [
            'types' => [
                'CELL_BOX',
            ],
            'title' => [
                '~(Akkubox) - (\d{5})~' => '$1 für $2er Akkuzellen',
            ],
        ],
    ],
    'base_urls' => [
        'E_CIGARETTE'        => 'e-zigaretten/',
        'POD_SYSTEM'         => 'e-zigaretten/pod-systeme/',
        'BOX_MOD'            => 'e-zigaretten/akkus/',
        'BOX_MOD_CELL'       => 'e-zigaretten/akkutraeger/',
        'E_PIPE'             => 'e-zigaretten/e-pfeifen/',
        'CLEAROMIZER'        => 'verdampfer/für Verdampferköpfe/',
        'CLEAROMIZER_ADA'    => 'verdampfer/für Selbstwickler/ada',
        'CLEAROMIZER_RTA'    => 'verdampfer/für Selbstwickler/rta/',
        'CLEAROMIZER_RDA'    => 'verdampfer/für Selbstwickler/rda/',
        'CLEAROMIZER_RDTA'   => 'verdampfer/für Selbstwickler/rdta/',
        'NICSALT_LIQUID'     => 'e-liquids/nikotinsalz/',
        'LIQUID'             => 'e-liquids/gebrauchsfertig/',
        'LIQUID_BOX'         => 'e-liquids/gebrauchsfertig/',
        'AROMA'              => 'e-liquids/aromen/',
        'SHAKE_VAPE'         => 'e-liquids/shake-and-vape/',
        'HEAD'               => 'e-zigaretten/koepfe/',
        'TANK'               => 'e-zigaretten/tanks/',
        'SEAL'               => 'e-zigaretten/dichtungen/',
        'DRIP_TIP'           => 'e-zigaretten/driptips/',
        'POD'                => 'e-zigaretten/pods/',
        'CARTRIDGE'          => 'e-zigaretten/pods/',
        'CELL'               => 'e-zigaretten/akkuzellen/',
        'CELL_BOX'           => 'e-zigaretten/akkuzellen/',
        'BASE'               => 'e-liquids/basen/',
        'CHARGER'            => 'e-zigaretten/ladegeraete/',
        'BAG'                => 'e-zigaretten/taschen/',
        'TOOL'               => 'e-zigaretten/werkzeug/',
        'WADDING'            => 'e-zigaretten/watte/',
        'WIRE'               => 'e-zigaretten/wickeldraht/',
        'SQUONKER_BOTTLE'    => 'e-zigaretten/squonker/',
        'VAPORIZER'          => 'e-zigaretten/vaporizer/',
        'SHOT'               => 'e-liquids/liquids/shots/',
        'CABLE'              => 'e-zigaretten/kabel/',
        'COIL'               => 'e-zigaretten/coils/',
        'RDA_BASE'           => 'e-zigaretten/base/',
        'MAGNET'             => 'e-zigaretten/magnet/',
        'MAGNET_ADAPTOR'     => 'e-zigaretten/magnet/',
        'ACCESSORY'          => 'e-zigaretten/accessoires/',
        'BATTERY_CAP'        => 'e-zigaretten/kappen/',
        'EXTENSION_KIT'      => 'e-zigaretten/umbau/',
        'CONVERSION_KIT'     => 'e-zigaretten/umbau/',
        'E_HOOKAH'           => 'e-zigaretten/e-hookahs/',
        'SQUONKER_BOX'       => 'e-zigaretten/squonker/',
        'EMPTY_BOTTLE'       => 'e-zigaretten/liquidflasche/',
        'EASY3_CAP'          => 'e-liquids/gebrauchsfertig/',
        'EASY4_CAP'          => 'e-liquids/gebrauchsfertig/',
        'DECK'               => 'e-zigaretten/decks/',
        'HEATING_PLATE'      => 'e-zigaretten/heizplatten/',
        'TOOL_HEATING_PLATE' => 'e-zigaretten/heizplatten/',
        'DRIP_TIP_CAP'       => 'e-zigaretten/driptips/',
        'TANK_PROTECTION'    => 'e-zigaretten/tanks/',
        'STORAGE'            => 'e-zigaretten/aufbewahrung/',
        'BATTERY_SLEEVE'     => 'e-zigaretten/kappen/',
        'CLEANING_SUPPLY'    => 'e-zigaretten/reinigung/',
        'COVER'              => 'e-zigaretten/covers/',
        'DISPLAY'            => 'e-zigaretten/display/',
        'SPARE_PARTS'        => 'e-zigaretten/ersatzteile/',
    ],
];
