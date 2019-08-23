<?php

return [
    'sizes' => [
        'urlMin' => 30,
        'urlMax' => 42,
        'titleMin' => 40,
        'titleMax' => 54,
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
                'EASY3_CAP'
            ],
            'title' => [
                '~(SC) - (Easy 3) - (.*) - (Caps)~' => '$1 - $3 - $4 für die $1 $2'
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
    ]
];
