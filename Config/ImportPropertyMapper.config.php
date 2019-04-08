<?php

return [
    'settings' => [
        'checkRegularExpressions' => true,
        'articleConfigFile'       => __DIR__ . '/../Config/article.config.php',
    ],

    'group_names'               => [
        'STAERKE'     => 'Nikotinstärke',
        'WIDERSTAND'  => 'Widerstand',
        'PACKUNG'     => 'Packungsgröße',
        'FARBE'       => 'Farbe',
        'DURCHMESSER' => 'Durchmesser',
        'GLAS'        => 'Glas',
        'MODEL'       => 'Ausführung',
    ],
    'option_names'              => [
        '100PG'                      => '100% PG',
        '100VG'                      => '100% VG',
        '24 GA*2+32 GA'              => '24 GA * 2 + 32 GA',
        '26 GA*3+36 GA'              => '26 GA * 3 + 36 GA',
        '26 GA*2+30 GA'              => '26 GA * 2 + 30 GA',
        '28 GA*2+30 GA'              => '28 GA * 2 + 30 GA',
        '28 GA*2+32 GA'              => '28 GA * 2 + 32 GA',
        '28 GA*3+36 GA'              => '28 GA * 3 + 36 GA',
        '30 GA*3+38 GA'              => '30 GA * 3 + 38 GA',
        '4,2ml'                      => '4,2 ml',
        '3,0ml'                      => '3,0 ml',
        '15ml'                       => '15 ml',
        '10ml'                       => '10 ml',
        '18ml'                       => '18 ml',
        '5ml'                        => '5 ml',
        '28ml'                       => '28 ml',
        '50PG / 50VG'                => 'VG/PG: 50/50',
        '70VG / 30PG'                => 'VG/PG: 70/30',
        '80VG / 20PG'                => 'VG/PG: 80/20',
        'Kompass bronze'             => 'kompass-bronze',
        'Kompass schwarz'            => 'kompass-schwarz',
        'auto pink'                  => 'auto-pink',
        'chrome'                     => 'chrom',
        'dunkler Marmor'             => 'dunkel-marmor',
        'gebürstete bronze'          => 'bronze (gebürstet)',
        'gebürsteter Stahl'          => 'stahl (gebürstet)',
        'gebürstetes schwarz-silber' => 'schwarz-silber (gebürstet)',
        'gebürstetes silber'         => 'silber (gebürstet)',
        'komplett schwarz'           => 'schwarz',
        'metallic grau'              => 'grau (metallic)',
        'metallic-resin'             => 'resin (metallic)',
        'mosaik grau'                => 'grau (mosaik)',
        'mosaik schwarz'             => 'schwarz (mosaik)',
        'mosaik schwarz-weiss'       => 'schwarz-weiß (mosaik)',
        'mosaik weiss'               => 'weiß (mosaik)',
        'mosaik rot'                 => 'rot (mosaik)',
        'regenbogen-gelb'            => 'gelb-regenbogen',
        'regenbogen-schwarz'         => 'schwarz-regenbogen',
        'schwarz / grün sprayed'     => 'schwarz-grün (sprayed)',
        'schwarz / rot sprayed'      => 'schwarz-rot (sprayed)',
        'schwarz / weiss sprayed'    => 'schwarz-weiß (sprayed)',
    ],

    'log'           => [
        'brand',
        'supplier',
        'option',
        'name',
        'replacement',
        'category',
    ],

    'retail_prices' => [
        [
            'criteria'     => [
                'brand'    => 'SC',
                'supplier' => 'InnoCigs',
                'type'     => 'LIQUID',
            ],
            'retail_price' => 2.50,
        ],
    ],

    'articles'      => include __DIR__ . '/article.config.php',

    'mapped_article_properties' => [
        'icNumber',
        'number',
        'name',
        'commonName',
        'type',
        'category',
        'supplier',
        'brand',
        'piecesPerPack',
        'dosage',
        'base',
    ],
    // A mapped article name is structured like so:
    // supplier [- Article group] - product name - additional stuff
    //
    // This array identifies the index of the product name
    // by supplier and article group. Default is 1.
    //
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


];