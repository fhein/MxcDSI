<?php

use MxcDropshipInnocigs\Models\Article;

return [
    'article_name_option_fixes' => [
        'blau-prisma' => 'prisma-blau',
        'minz-grün' => 'minzgrün',
        'chrom-prisma' => [
            'chrome-prisma',
            'prisma-chrom'
        ],
        'gold-prisma' => 'prisma-gold',
        'gunmetal-prisma' => 'prisma-gunmetal',
        'regenbogen-prisma' => 'prisma-regenbogen',
        'rot-prisma' => 'prisma-rot',
        'grün-prisma' => 'prisma-grün',
        'gunmetal chrome' => 'gunmetal-chrom',
        'auto pink' => 'auto-pink',
        '10 mg/ml' => '- 10mg/ml',
        '20 mg/ml' => '- 20mg/ml',
        'grau-weiß' => 'grau-weiss',
        '0,25 Ohm' => '0,25',
        '0,4 Ohm' => '0,4',
        '1000er Packung' => '1000er Packubng',
        '20er Packung' => [
            '20er Packug',
            '(20 Stück Pro Packung)'
        ],
        'resin-rot' => 'Resin rot',
        'resin-gelb' => 'Resin gelb',
        '0 mg/ml'   => '0 mg/mgl',
        'weiss' => ' weiß',
        '1,5 mg/ml' => '1,5 ml',
        '3 mg/ml'   => '3mg/ml',
        'gebürsteter stahl' => 'gebürsteter Stahl',
        'dunkelgrün' => 'dunkel grün',
        '6 mg/ml' => '6mg/ml',
        'matt-schwarz' => 'matt schwarz',
        'schwarz-weiss' => 'schwarz-weiß',
        'schwarz-weiß' => 'schwarz-weiss',
        'weiß' => 'weiss',
        '50PG / 50VG' => [
            '50PG/50VG',
            '50VG/50PG'
        ],
        '70VG / 30PG' => '70VG/30PG',
        '80VG / 20PG' => '80VG/20PG',
        'regenbogen' => 'iridescent',
        '28 GA' => '28GA',
        '26 GA' => '26GA',
        '24 GA' => '24GA',
        '22 GA' => '22GA',
        '26 GA*3+36 GA' => '26GA*3+36GA',
        '28 GA*3+36 GA' => '28GA*3+36GA',
        '30 GA*3+36 GA' => '30GA*3+38GA',
        '24 GA*2+32 GA' => '24GA*2+32GA',
        '28 GA*2+32 GA' => '28GA*2+32GA',
        '26 GA+32 GA' => '26GA+32GA',
        '28 GA*2+30 GA' => '28GA*2+30GA'
    ],

    'article_codes'     => [],
    'article_names'    => [
        'Vampire Vape Applelicious - E-Zigaretten Liquid' => 'Vampire Vape - Applelicious - E-Zigaretten Liquid',
    ],
    'article_name_parts_rexp' => [
        '~0ml\/ml~'                         => '0mg/ml',
        '~(\d\d*)m~'                        => '$1 m',
        '~(\d) ?mAH~'                       => '$1 mAh',
        '~ (\d+) mAh~'                      => ', $1 mAh',
        '~(\d)(\d{3}) mAh~'                 => '$1.$2 mAh',
        '~([^,\-]) (\d) m$~'                => '$1 - $2 m',
        '~ (\d,\d+) Ohm~'                   => ', $1 Ohm',
        '~, (\d,\d+ Ohm) Heads?~'           => ' Heads, $1',
        '~(\d)ml~'                          => '$1 ml',
        '~(\d) ?mg~'                        => '$1 mg/ml',
        '~ml\/ml~'                          => 'ml',
        '~ (\d+) Watt~'                     => ', $1 Watt',
        '~ml - (\d)~'                       => 'ml, $1',
        '~([^,\-]) (\d(,\d+)?) ?ml~'        => '$1, $2 ml',
        '~(\d+)ML(.*Leerflasche)~'          =>'$2, $1 ml',
        '~ml (\d)~'                         => 'ml, $1',
        '~([^,\-]) (\d+) ml$~'              => '$1 - $2 ml',
        '~([^,\-]) (\d+) mg\/ml$~'          => '$1 - $2 mg/ml',
        '~(\d+ mg/ml),? (\d+ ml)~'          => '$2, $1',
        '~([^,\-]) (\d+ ml, \d+ mg/ml)~'    => '$1 - $2',
        '~(Treib.*100 ml$)~'                => '$1, 0 mg/ml',
        '~Rebelz (- Aroma)(.*)(- \d+ ml)~'  => 'Rebelz - $2 $1 $3',
        '~Vape( -)?( Aroma)(.*)(- \d+ ml)~' => 'Vape - $3 -$2 $4',
        '~(Vampire Vape) ([^\-])~'          => '$1 - $2',
        '~(VLADS VG) Liquid (-.*)~'         => '$1 $2 - Liquid',
        '~(Bull) (- Aroma)(.*)(- \d+ ml)~'  => '$1 - $3 $2 $4',
        '~ - ?$~'                           => '',
        '~\s+~'                             => ' ',
        '~(-\s)+~'                          => '- ',
        // @todo: Heads nach Head
    ],
    'article_name_parts' => [
        'SINUOUS' => 'Sinuous',
        'Aroma - Liquid für E-Zigaretten' => '- Aroma',
        'E-Zigaretten Nikotinsalz Liquid' => 'Nikotinsalz-Liquid',
        '- Liquid für E-Zigaretten' => '- Liquid',
        'E-Zigaretten Liquid' => '- Liquid',
        'Heads Heads' => 'Heads',
        'AsMODus' => 'asMODus',
        'mit,' => 'mit',
        'Pro P' => 'pro P',
        'pro Pack)' => 'pro Packung)',
        'St. pro' => 'Stück pro',
        '5er Pack' => '5 Stück pro Packung',
        '50VG/50PG' => 'PG/VG 50:50,',
        '50PG/50VG' => 'PG/VG 50:50,',
        '10er Packung' => '(10 Stück pro Packung)',
        '(Dual Coil), 1,5 Ohm' => '- Dual Coil, 1,5 Ohm',
        'mAh 40A' => 'mAh, 40 A',
        'P80 Watt' => 'P80, 80 Watt',
        '+ Adapter' => ', mit Adapter',
        '- -' => '-',
    ],
    'group_names' => [
        'STAERKE'       => 'Nikotinstärke',
        'WIDERSTAND'    => 'Widerstand',
        'PACKUNG'       => 'Packungsgröße',
        'FARBE'         => 'Farbe',
        'DURCHMESSER'   => 'Durchmesser',
        'GLAS'          => 'Glas',
    ],
    'option_names'      => [
        'minz-grün' => 'minzgrün',
    ],
    'variant_codes'     => [],
    'manufacturers'     => [
        'Smok' => [
            'supplier'  => 'Smoktech',
            'brand'     => 'Smok'
        ],
        'Renova' => [
            'supplier'  => 'Vaporesso',
            'brand'     => 'Renova',
        ],
        'Dexter`s Juice Lab' => [
            'brand' => 'Dexter\'s Juice Lab',
            'supplier' => 'Dexter\'s Juice Lab',
        ]
    ],
    'categories' => [
        'Alt > Joyetech 510-T > Zubehör' => 'Zubehör > Joyetech',
    ],
    'filters' => [
        'update' => [
            [
                'entity' => Article::class,
                'andWhere' => [
                    [ 'field' => 'name', 'operator' => 'LIKE', 'value' => '%iquid%' ]
                ],
                'set' => [ 'accepted' => false, 'active' => false ],
            ],
            [
                'entity' => Article::class,
                'andWhere' => [
                    [ 'field' => 'name', 'operator' => 'LIKE', 'value' => '%Aroma%' ]
                ],
                'set' => [ 'accepted' => false, 'active' => false, ]
            ],
            [
                'entity' => Article::class,
                'andWhere' => [
                    [ 'field' => 'brand', 'operator' => 'LIKE', 'value' => 'DVTCH Amsterdam' ]
                ],
                'set' => [ 'accepted' => false, 'active' => false, ]
            ],
        ],
    ],
    'articles' => 'This key is reserverd for PropertyMapperFactory',
];