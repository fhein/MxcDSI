<?php

use MxcDropshipInnocigs\Models\ArticleProperties;

return [
    'category_type_map' => [
        'Akkus'                         => ArticleProperties::TYPE_BOX_MOD,
        'Akkuträger'                    => ArticleProperties::TYPE_BOX_MOD_CELL,
        'Verdampfer'                    => ArticleProperties::TYPE_CLEAROMIZER,
        'E-Zigaretten'                  => ArticleProperties::TYPE_E_CIGARETTE,
        'E-Pfeifen'                     => ArticleProperties::TYPE_E_PIPE,
        'Vaporizer'                     => ArticleProperties::TYPE_VAPORIZER,
        'Liquids'                       => ArticleProperties::TYPE_LIQUID,
        'Aromen'                        => ArticleProperties::TYPE_AROMA,
        'Shake & Vape'                  => ArticleProperties::TYPE_SHAKE_VAPE,
        'Zubehör > Verdampferköpfe'     => ArticleProperties::TYPE_HEAD,
        'Zubehör > Kabel & Stecker'     => ArticleProperties::TYPE_CABLE,
        'Zubehör > Dichtungen'          => ArticleProperties::TYPE_SEAL,
        'Zubehör > Taschen'             => ArticleProperties::TYPE_BAG,
        'Zubehör > Werkzeug'            => ArticleProperties::TYPE_TOOL,
        'Zubehör > Glastanks'           => ArticleProperties::TYPE_TANK,
        'Zubehör > Mundstücke & Schutz' => ArticleProperties::TYPE_DRIP_TIP,
        'Zubehör > Ladegeräte'          => ArticleProperties::TYPE_CHARGER,
        'Liquids > Easy 3 Caps'         => ArticleProperties::TYPE_POD,
        'Zubehör > Accessoires'         => ArticleProperties::TYPE_ACCESSORY,
    ],

    'name_type_map' => [
        '~Akkuzelle~'                          => ArticleProperties::TYPE_CELL,
        '~Akkubox~'                            => ArticleProperties::TYPE_CELL_BOX,
        '~Watte~'                              => ArticleProperties::TYPE_WADDING,
        '~Wickeldraht~'                        => ArticleProperties::TYPE_WIRE,
        '~Coil~'                               => ArticleProperties::TYPE_COIL,
        '~Cartridge~'                          => ArticleProperties::TYPE_CARTRIDGE,
        '~Pod~'                                => ArticleProperties::TYPE_POD,
        '~Guillotine V2 - Base~'               => ArticleProperties::TYPE_RDA_BASE,
        '~Shot~'                               => ArticleProperties::TYPE_SHOT,
        '~Leerflasche~'                        => ArticleProperties::TYPE_BOTTLE,
        '~Base~'                               => ArticleProperties::TYPE_BASE,
        '~Ersatzmagnet~'                       => ArticleProperties::TYPE_MAGNET,
        '~Magnet-Adapter~'                     => ArticleProperties::TYPE_MAGNET_ADAPTER,
        '~(Batteriekappe)|(Batteriehülse)~'    => ArticleProperties::TYPE_BATTERY_CAP,
        '~(Squonker Flasche)|(Liquidflasche)~' => ArticleProperties::TYPE_SQUONKER_BOTTLE,
    ],
];