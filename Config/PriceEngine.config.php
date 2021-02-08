<?php
return array(
    'discounts' => [
        [
            'price' => 0,
            'discount' => 0,
        ],
        [
            'price' => 75,
            'discount' => 7.5
        ]
    ],
    // price rules can get defined by type
    'rules' => array(
        '_default' => array(
            'price' => null,
            'margin_min_percent' => 25,
            'margin_min_abs' => 1,
            'margin_max_percent' => null,
            'margin_max_abs' => 15,
            'margin_min_discount_abs' => 8,
        ),
        'CLEAROMIZER' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'TANK' => array(
            '_default' => array(
                'margin_max_percent' => 60,
            ),
        ),
        'LIQUID' => array(
            '_default' => array(
                'margin_min_abs' => 0.85,
            ),
        ),
        'HEAD' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'CHARGER' => array(
            '_default' => array(
                'margin_max_percent' => 35,
            ),
        ),
        'DRIP_TIP' => array(
            '_default' => array(
            ),
        ),
        'LIQUID_BOX' => array(
            '_default' => array(
            ),
        ),
        'CABLE' => array(
            '_default' => array(
                'margin_max_percent' => 60,
            ),
        ),
        'CELL' => array(
            '_default' => array(
            ),
        ),
        'E_CIGARETTE' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'SEAL' => array(
            '_default' => array(
            ),
        ),
        'BOX_MOD_CELL' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'BAG' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'CELL_BOX' => array(
            '_default' => array(
                'margin_max_percent' => 65,
            ),
        ),
        'BOX_MOD' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'DRIP_TIP_CAP' => array(
            '_default' => array(
            ),
        ),
        'AROMA' => array(
            '_default' => array(
            ),
        ),
        'SHOT' => array(
            '_default' => array(
                'margin_min_abs' => 0.85,
            ),
        ),
        'SHAKE_VAPE' => array(
            '_default' => array(
            ),
        ),
        'EMPTY_BOTTLE' => array(
            '_default' => array(
                'margin_min_abs' => 0.85,
            ),
        ),
        'E_HOOKAH' => array(
            '_default' => array(
            ),
        ),
        'BATTERY_CAP' => array(
            '_default' => array(
                'margin_min_abs' => 0.85,
            ),
        ),
        'SQUONKER_BOTTLE' => array(
            '_default' => array(
                'margin_min_abs' => 0.85,
                'margin_max_percent' => 45,
            ),
        ),
        'RDA_BASE' => array(
            '_default' => array(
                'margin_min_abs' => 0.85,
            ),
        ),
        'CLEAROMIZER_RDA' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'CARTRIDGE' => array(
            '_default' => array(
                'margin_max_percent' => 50,
            ),
        ),
        'POD_SYSTEM' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
            'Smoktech' => [
                'SK100R2' => [
                    '_default' => [
                        'margin_min_percent' => 15,
                    ]
                ]
            ]
        ),
        'EASY3_CAP' => array(
            '_default' => array(
            ),
        ),
        'EASY4_CAP' => array(
            '_default' => array(
            ),
        ),
        'POD' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'TOOL_HEATING_PLATE' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'SQUONKER_BOX' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'WADDING' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'CLEAROMIZER_RTA' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'COIL' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'WIRE' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'TOOL' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'DECK' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'E_PIPE' => array(
            '_default' => array(
                'margin_max_percent' => 45,
            ),
        ),
        'STORAGE' => array(
            '_default' => array(
            ),
        ),
        'CLEAROMIZER_RDTA' => array(
            '_default' => array(
                'margin_max_percent' => 40,
            ),
        ),
        'DISPLAY' => array(
            '_default' => array(
            ),
        ),
        'SPARE_PARTS' => array(
            '_default' => array(
            ),
        ),
        'ACCESSORY' => array(
            '_default' => array(
            ),
        ),
        'COVER' => array(
            '_default' => array(
            ),
        ),
        'BASE' => array(
            '_default' => array(
                'margin_max_percent' => 50,
            ),
        ),
        'VAPORIZER' => array(
            '_default' => array(
                'price' => null,
                'margin_max_percent' => 40,
            ),
        ),
        'EXTENSION_KIT' => array(
            '_default' => array(
            ),
        ),
    ),
);
