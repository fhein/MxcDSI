<?php
return array(
    'product_categories' => array(
        0 => 'E-ZIGARETTTEN',
        1 => 'E-LIQUIDS',
        2 => 'SHAKE & VAPE',
        3 => 'AROMEN',
        4 => 'AKKUTRÄGER',
        5 => 'SELBSTWICKLER',
        6 => 'VERDAMPFER',
        7 => 'ZUBEHÖR',
    ),
    'flavor_category_map' => array(
        'E-LIQUIDS' => array(
            'title' => 'E-Liquids - Geschmack: ##flavor## - online kaufen!',
            'description' => 'E-Liquids für die E-Zigarette ✓ Geschmack: ##flavor## ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'E-Liquid,Liquid,E-Zigarette,günstig,kaufen,Geschmacksrichtung,##flavor##',
            'h1' => 'E-LIQUIDS - ##flavor##',
        ),
        'SHAKE & VAPE' => array(
            'title' => 'Shake & Vape - Geschmack: ##flavor## - online kaufen!',
            'description' => 'Shake & Vape für die E-Zigarette ✓ Geschmack: ##flavor## ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Shake,Vape,Shake&Vape,E-Liquid,Liquid,E-Zigarette,günstig,kaufen,Geschmacksrichtung,##flavor##',
            'h1' => 'SHAKE & VAPE - ##flavor##',
        ),
        'AROMEN' => array(
            'title' => 'E-Liquid Aroma - Geschmack: ##flavor## - online kaufen!',
            'description' => 'Aroma für E-Liquid ✓ Geschmack: ##flavor## ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Aromen,Aroma,E-Liquid,Liquid,E-Zigarette,günstig,kaufen,Geschmacksrichtung,##flavor##',
            'h1' => 'AROMEN - ##flavor##',
        ),
    ),
    'flavor_category_cms_description_map' => array(
        'E-LIQUIDS' => '<p>In der Kategorie <b>##flavorcategory##</b> zeigen wir alle unsere <b>E-Liquids</b>, die eine oder mehrere dieser Geschmacksnoten aufweisen:</p>',
        'AROMEN' => '<p>Sehen Sie sich in dieser Kategorie <b>##flavorcategory##</b> alle unsere <b>Aromen</b> an, die einen der folgenden Geschmacke in der Beschreibung haben:</p>',
        'SHAKE & VAPE' => '<p>In der Kategorie <b>##flavorcategory##</b> zeigen wir alle unsere <b>Shake & Vape E-Liquids</b>, die eine oder mehrere dieser Geschmacksnoten aufweisen:</p>',
    ),
    'type_category_map' => array(
        0 => array(
            'types' => array(
                0 => 'E_CIGARETTE',
                1 => 'E_PIPE',
            ),
            'path' => 'E-ZIGARETTEN',
        ),
        1 => array(
            'types' => array(
                0 => 'BOX_MOD',
                1 => 'BOX_MOD_CELL',
                2 => 'SQUONKER_BOX',
            ),
            'path' => 'AKKUTRÄGER',
        ),
        2 => array(
            'types' => array(
                0 => 'CLEAROMIZER',
            ),
            'path' => 'VERDAMPFER',
        ),
        3 => array(
            'types' => array(
                0 => 'CLEAROMIZER_RTA',
                1 => 'CLEAROMIZER_RDA',
                2 => 'CLEAROMIZER_RDTA',
                3 => 'CLEAROMIZER_RDSA',
            ),
            'path' => 'SELBSTWICKLER > Verdampfer',
        ),
        4 => array(
            'types' => array(
                0 => 'LIQUID',
                1 => 'LIQUID_BOX',
                2 => 'EASY3_CAP',
            ),
            'path' => 'E-LIQUIDS',
            'append' => ['flavor'],
            'seo' => array(
                'flavor' => array(
                    'title' => 'E-Liquids - Geschmack: ##flavor## - online kaufen!',
                    'description' => 'E-Liquids für die E-Zigarette ✓ Geschmack: ##flavor## ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
                    'keywords' => 'E-Liquid,Liquid,E-Zigarette,günstig,kaufen,Geschmacksrichtung,##flavor##',
                    'h1' => 'E-LIQUIDS - ##flavor##',
                ),
            ),
        ),
        6 => array(
            'types' => array(
                0 => 'AROMA',
            ),
            'path' => 'AROMEN',
            'append' => ['flavor'],
            'seo' => array(
                'flavor' => array(
                    'title' => 'Shake & Vape E-Liquids - Geschmack: ##flavor## - online kaufen!',
                    'description' => 'Shake & Vape E-Liquids für die E-Zigarette ✓ Geschmack: ##flavor## ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
                    'keywords' => 'E-Liquid,Liquid,E-Zigarette,günstig,kaufen,Geschmacksrichtung,##flavor##',
                    'h1' => 'SHAKE & VAPE E-LIQUIDS - ##flavor##',
                ),
            ),
        ),
        7 => array(
            'types' => array(
                0 => 'SHAKE_VAPE',
            ),
            'path' => 'SHAKE & VAPE',
            'append' => ['flavor'],
            'seo' => array(
                'flavor' => array(
                    'title' => 'Aromen für E-Liquids - Geschmack: ##flavor## - online kaufen!',
                    'description' => 'Aromen für E-Liquids für die E-Zigarette ✓ Geschmack: ##flavor## ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
                    'keywords' => 'Aroma,E-Liquid,Liquid,E-Zigarette,günstig,kaufen,Geschmacksrichtung,##flavor##',
                    'h1' => 'AROMEN FÜR E-LIQUIDS - ##flavor##',
                ),
            ),
        ),
        8 => array(
            'types' => array(
                0 => 'HEAD',
            ),
            'path' => 'ZUBEHÖR > Verdampferköpfe',
        ),
        9 => array(
            'types' => array(
                0 => 'TANK',
                1 => 'TANK_PROTECTION',
            ),
            'path' => 'ZUBEHÖR > Glastanks & Schutz',
        ),
        10 => array(
            'types' => array(
                0 => 'DRIP_TIP',
                1 => 'DRIP_TIP_CAP',
            ),
            'path' => 'ZUBEHÖR > Mundstücke & Schutz',
        ),
        11 => array(
            'types' => array(
                0 => 'SEAL',
            ),
            'path' => 'ZUBEHÖR > Dichtungen',
        ),
        12 => array(
            'types' => array(
                0 => 'POD',
                1 => 'CARTRIDGE',
            ),
            'path' => 'ZUBEHÖR > Pods & Cartridges',
        ),
        13 => array(
            'types' => array(
                0 => 'CELL',
                1 => 'CELL_BOX',
            ),
            'path' => 'ZUBEHÖR > Akkuzellen & -boxen',
        ),
        14 => array(
            'types' => array(
                0 => 'BASE',
                1 => 'SHOT',
            ),
            'path' => 'ZUBEHÖR > Basen & Shots',
        ),
        15 => array(
            'types' => array(
                0 => 'CHARGER',
                1 => 'CABLE',
            ),
            'path' => 'ZUBEHÖR > Ladegeräte & -kabel',
        ),
        16 => array(
            'types' => array(
                0 => 'SQUONKER_BOTTLE',
            ),
            'path' => 'ZUBEHÖR > Squonker-Flaschen',
        ),
        17 => array(
            'types' => array(
                0 => 'BAG',
            ),
            'path' => 'ZUBEHÖR > Taschen & Etuis',
        ),
        18 => array(
            'types' => array(
                0 => 'TOOL',
                1 => 'TOOL_HEATING_PLATE',
            ),
            'path' => 'SELBSTWICKLER > Werkzeug',
        ),
        19 => array(
            'types' => array(
                0 => 'WADDING',
            ),
            'path' => 'SELBSTWICKLER > Watte',
        ),
        20 => array(
            'types' => array(
                0 => 'WIRE',
            ),
            'path' => 'SELBSTWICKLER > Wickeldraht',
        ),
        21 => array(
            'types' => array(
                0 => 'DECK',
                1 => 'RDA_BASE',
            ),
            'path' => 'SELBSTWICKLER > Decks',
        ),
        23 => array(
            'types' => array(
                0 => 'COIL',
                1 => 'HEATING_PLATE',
            ),
            'path' => 'SELBSTWICKLER > Coils',
        ),
        24 => array(
            'types' => array(
                0 => 'EXTENSION_KIT',
                1 => 'CONVERSION_KIT',
            ),
            'path' => 'SELBSTWICKLER > Umbaukits',
        ),
        25 => array(
            'types' => array(
                0 => 'EMPTY_BOTTLE',
            ),
            'path' => 'ZUBEHÖR > Leerflaschen',
        ),
        26 => array(
            'types' => array(
                0 => 'CLEANING_SUPPLY',
            ),
            'path' => 'ZUBEHÖR > Reinigung',
        ),
        27 => array(
            'types' => array(
                0 => 'ACCESSORY',
            ),
            'path' => 'ZUBEHÖR > Accessoires',
        ),
        32 => array(
            'types' => array(
                0 => 'COVER',
            ),
            'path' => 'ZUBEHÖR > Abdeckungen',
        ),
        28 => array(
            'types' => array(
                0 => 'E_HOOKAH',
                1 => 'VAPORIZER',
            ),
            'path' => 'E-ZIGARETTEN > E-Hookah & Vaporizer',
        ),
        29 => array(
            'types' => array(
                0 => 'BATTERY_CAP',
                1 => 'BATTERY_SLEEVE',
            ),
            'path' => 'ZUBEHÖR > Batteriekappen & -hülsen',
        ),
        30 => array(
            'types' => array(
                0 => 'MAGNET',
                1 => 'MAGNET_ADAPTOR',
            ),
            'path' => 'ZUBEHÖR > Magnet & Magnetadapter',
        ),
        31 => array(
            'types' => array(
                0 => 'STORAGE',
            ),
            'path' => 'ZUBEHÖR > Aufbewahrung',
        ),
    ),
    'category_seo_items' => array(
        'E-ZIGARETTEN' => array(
            'title' => 'E-Zigaretten günstig online kaufen!',
            'description' => 'E-Zigaretten günstig kaufen ✓ E-Zigaretten für Einsteiger und Profis ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'E-Zigarette,günstig,kaufen',
            'h1' => 'ZUBEHÖR FÜR DIE E-ZIGARETTE',
        ),
        'E-ZIGARETTEN > E-Hookah & Vaporizer' => array(
            'title' => 'E-Hookah und Vaporizer günstig online kaufen!',
            'description' => 'E-Hookah und Vaporizer günstig online kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'E-Shisha,E-Hookah,Vaporizer,günstig,kaufen',
            'h1' => 'E-HOOKAH UND VAPORIZER',
        ),
        'ZUBEHÖR' => array(
            'title' => 'Zubehör für E-Zigaretten günstig online kaufen!',
            'description' => 'Zubehör für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Verdampfer, Verdampferkopf,Coil,E-Zigarette,günstig,kaufen',
            'h1' => 'ZUBEHÖR FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Aufbewahrung' => array(
            'title' => 'Vitrinen für E-Zigaretten günstig online kaufen!',
            'description' => 'Vitrinen für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Aufbewahrung,Vitrine,E-Zigarette,günstig,kaufen',
            'h1' => 'AUFBEWAHRUNG VON E-ZIGARETTEN UND VERDAMPFERN',
        ),
        'ZUBEHÖR > Magnet & Magnetadapter' => array(
            'title' => 'Magnete und -adapter für E-Zigaretten günstig online kaufen!',
            'description' => 'Magnete und -adapter für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Magnet,Magnetadapter,E-Zigarette,günstig,kaufen',
            'h1' => 'Magnete und Magnetadapter',
        ),
        'ZUBEHÖR > Batteriekappen & -hülsen' => array(
            'title' => 'Batteriekappen für E-Zigaretten günstig online kaufen!',
            'description' => 'Batteriekappen für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Batteriekappe,Kappe,E-Zigarette,günstig,kaufen',
            'h1' => 'BATTERIEKAPPEN UND -HÜLSEN',
        ),
        'ZUBEHÖR > Abdeckungen' => array(
            'title' => 'Abdeckungen für E-Zigaretten günstig online kaufen!',
            'description' => 'Abdeckungen für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Abdeckung,Cover,E-Zigarette,günstig,kaufen',
            'h1' => 'COVERS UND ABDECKUNGEN FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Accessoires' => array(
            'title' => 'Accessoires für E-Zigaretten günstig onliine kaufen!',
            'description' => 'Accessoires für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Accessoire,E-Zigarette,günstig,kaufen',
            'h1' => 'ACCESSOIRES FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Reinigung' => array(
            'title' => 'Renigungszubehör für E-Zigaretten günstig kaufen!',
            'description' => 'Reinigungszubehör für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Reinigung,Zubehör,Reinigungszubehör,E-Zigarette,günstig,kaufen',
            'h1' => 'REINIGUNGSMITTEL FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Leerflaschen' => array(
            'title' => 'Leerflaschen für E-Liquid günstig online kaufen!',
            'description' => 'Leerflaschen für E-Liquid günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Leerflasche,Flasche,Liquid,E-Liquid,günstig,kaufen',
            'h1' => 'LEERFLASCHEN FÜR E-LIQUIDS',
        ),
        'ZUBEHÖR > Taschen & Etuis' => array(
            'title' => 'Taschen & Etuis für E-Zigaretten günstig online kaufen!',
            'description' => 'Taschen & Etuis für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Tasche,Etui,E-Zigarette,günstig,kaufen',
            'h1' => 'TASCHEN UND ETUIS FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Squonker-Flaschen' => array(
            'title' => 'Squonker-Flaschen für E-Zigaretten günstig online kaufen!',
            'description' => 'Squonker-Flaschen für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Squonker-Bottle,Squonkerflasche,Squonker-Flasche,E-Zigarette,günstig,kaufen',
            'h1' => 'SQUONKER-FLASCHEN FÜR SQUONKER MODS',
        ),
        'ZUBEHÖR > Verdampferköpfe' => array(
            'title' => 'Verdampferköpfe für E-Zigaretten günstig online kaufen!',
            'description' => 'Verdampferköpfe für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Verdampfer, Verdampferkopf,Coil,E-Zigarette,günstig,kaufen',
            'h1' => 'VERDAMPFERKÖPFE',
        ),
        'ZUBEHÖR > Basen & Shots' => array(
            'title' => 'Basis und Nikotin-Shots für die E-Liquid günstig online kaufen!',
            'description' => 'Basis und Nikotin-Shots für E-Liquid günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Base,Basis,Nikotinshot,Nikotin-Shot,Liquid,E-Liquid,E-Zigarette,günstig,kaufen',
            'h1' => 'BASEN & NIKOTIN-SHOTS FÜR E-LIQUIDS',
        ),
        'ZUBEHÖR > Ladegeräte & -kabel' => array(
            'title' => 'Ladegeräte und -kabel für E-Zigaretten günstig online kaufen!',
            'description' => 'Ladegeräte und -kabel für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Ladegerät,Ladekabel,Kabel,E-Zigarette,günstig,kaufen',
            'h1' => 'LADEGERÄTE UND -KABEL FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Akkuzellen & -boxen' => array(
            'title' => 'Akkus für E-Zigaretten günstig online kaufen!',
            'description' => 'Akkus für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Akku,Akkuzelle,Batterie,E-Zigarette,günstig,kaufen',
            'h1' => 'AKKUZELLEN UND -BOXEN FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Pods & Cartridges' => array(
            'title' => 'Pods für E-Zigaretten günstig online kaufen!',
            'description' => 'Pods für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Pod,Cartridge,E-Zigarette,günstig,kaufen',
            'h1' => 'PODS UND CARTRIDGES FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Dichtungen' => array(
            'title' => 'Ersatz-Dichtungen für E-Zigaretten günstig online kaufen!',
            'description' => 'Ersatz-Dichtungen für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Dichtung,Ersatzdichtung,Ersatz-Dichtung,E-Zigarette,günstig,kaufen',
            'h1' => 'ERSATZ-DICHTUNGEN FÜR VERDAMPFER',
        ),
        'ZUBEHÖR > Mundstücke & Schutz' => array(
            'title' => 'Mundstücke für E-Zigaretten günstig online kaufen!',
            'description' => 'Mundstücke für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Mundstück,E-Zigarette,günstig,kaufen',
            'h1' => 'MUNDSTÜCKE UND SCHUTZ FÜR DIE E-ZIGARETTE',
        ),
        'ZUBEHÖR > Glastanks & Schutz' => array(
            'title' => 'Glastanks für E-Zigaretten günstig online kaufen!',
            'description' => 'Glastanks für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Glastank,E-Zigarette,günstig,kaufen',
            'h1' => 'GLASTANKS FÜR VERDAMPFER',
        ),
        'SELBSTWICKLER' => array(
            'title' => 'E-Zigarette: Produkte für Selbstwickler günstig online kaufen!',
            'description' => 'Produkte für Selbstwickler ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Verdampfer,Clearomizer,Selbstwickler,Coil,Deck,Watte,Werkzeug,E-Zigarette,günstig,kaufen',
            'h1' => 'PRODUKTE FÜR SELBSTWICKLER',
        ),
        'SELBSTWICKLER > Umbaukits' => array(
            'title' => 'Umbaukits für Selbstwickel-Verdampfer günstig kaufen!',
            'description' => 'Umbaukits für Selbstwickel-Verdampfer ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Steam Crave,Aromamizer,Erweiterung,Umbau,Erweiterungskit,Umbaukit,E-Zigarette,günstig,kaufen',
            'h1' => 'UMBAUKITS FÜR SELBSTWICKEL-VERDAMPFER',
        ),
        'SELBSTWICKLER > Coils' => array(
            'title' => 'Coils und Wicklungen für E-Zigaretten günstig online kaufen!',
            'description' => 'Coils und Wicklungen für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Coil,Wicklung,Spule,E-Zigarette,günstig,kaufen',
            'h1' => 'COILS FÜR SELBSTWICKLER',
        ),
        'SELBSTWICKLER > Decks' => array(
            'title' => 'E-Zigarette | Base & Deck für Selbstwickler günstig online kaufen!',
            'description' => 'Bases & Decks für E-Zigaretten günstig kaufen ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Base,Deck,Selbstwickler,E-Zigarette,günstig,kaufen',
            'h1' => 'BASES UND DECKS FÜR SELBSTWICKLER',
        ),
        'SELBSTWICKLER > Wickeldraht' => array(
            'title' => 'E-Zigarette | Wickeldraht für Selbstwickler günstig online kaufen!',
            'description' => 'Wickeldraht für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Wickeldraht,Draht,Selbstwickler,E-Zigarette,günstig,kaufen',
            'h1' => 'WICKELDRAHT FÜR SELBSTWICKLER',
        ),
        'SELBSTWICKLER > Watte' => array(
            'title' => 'E-Zigarette | Watte für Selbstwickler günstig online kaufen!',
            'description' => 'Watte für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Watte,Selbstwickler,E-Zigarette,günstig,kaufen',
            'h1' => 'WATTE FÜR SELBSTWICKLER',
        ),
        'SELBSTWICKLER > Werkzeug' => array(
            'title' => 'Werkzeug für E-Zigaretten günstig online kaufen!',
            'description' => 'Werkzeug für E-Zigaretten günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Werkzeug,E-Zigarette,günstig,kaufen',
            'h1' => 'WERKZEUG FÜR SELBSTWICKLER',
        ),
        'SELBSTWICKLER > Verdampfer' => array(
            'title' => 'Selbstwickel-Verdampfer für die E-Zigaratte günstig online kaufen!',
            'description' => 'Selbstwickel-Verdampfer günstig kaufen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Selbstwickler,Verdampfer,Selbstwickel-Verdampfer,Clearomizer,Atomizer,E-Zigarette,günstig,kaufen',
            'h1' => 'SELBSTWICKEL-VERDAMPFER',
        ),
        'SHAKE & VAPE' => array(
            'title' => 'Shake & Vape E-Liquids für E-Zigaretten günstig online kaufen!',
            'description' => 'Shake & Vape E-Liquids - Shortfills - günstig kaufen ✓ Viele Geschmacksrichtungen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Shake & Vape,Longfill,E-Liquid,Liquid,E-Zigarette,günstig,kaufen',
            'h1' => 'SHAKE & VAPE E-LIQUIDS',
        ),
        'AROMEN' => array(
            'title' => 'Aromen für E-Zigaretten günstig online kaufen!',
            'description' => 'Aromen und Longfills günstig kaufen ✓ Viele Geschmacksrichtungen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Aroma,Longfill,E-Zigarette,günstig,kaufen',
            'h1' => 'AROMEN ZUR HERSTELLUNG VON E-LIQUID',
        ),
        'E-LIQUIDS' => array(
            'title' => 'E-Liquids für E-Zigaretten günstig online kaufen!',
            'description' => 'E-Liquids für E-Zigaretten günstig kaufen ✓ Viele Geschmacksrichtungen ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Liquid,E-Liquid,günstig,kaufen,E-Zigarette',
            'h1' => 'E-LIQUIDS',
        ),
        'VERDAMPFER' => array(
            'title' => 'Verdampfer und Clearomizer für E-Zigaretten günstig online kaufen!',
            'description' => 'Verdampfer für E-Zigaretten günstig kaufen ✓ Für Einsteiger und Profis ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Verdampfer,Clearomizer,Atomizer,Vaporizer,E-Zigarette,günstig,kaufen',
            'h1' => 'VERDAMPFER FÜR DIE E-ZIGARETTE',
        ),
        'AKKUTRÄGER' => array(
            'title' => 'Akkuträger und Box Mods für E-Zigaretten günstig online kaufen!',
            'description' => 'Akkuträger und Box Mods günstig kaufen ✓ Für Einsteiger und Profis ✓ Große Auswahl ✓ Faire Preise ✓ Rascher Versand ► Besuchen Sie vapee.de!',
            'keywords' => 'Akkuträger,Box,Box Mod,E-Zigarette,günstig,kaufen',
            'h1' => 'AKKUTRÄGER',
        ),
    ),
);
