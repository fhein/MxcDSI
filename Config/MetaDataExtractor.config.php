<?php

return [
    'types' => [
        // 'BOX_MOD',
        // 'BOX_MOD_CELL',
        // 'SQUONKER_BOX',
        'CLEAROMIZER',

    ],

    'defaults' => [
        'POD_SYSTEM' => [
            'BATTERIES',
            'TANK_CAPACITY',
            'POWER',
            'HEAD_CHANGEABLE',
            'INHALATION_STYLE',
        ],
        'E_CIGARETTE' => [
            'BATTERIES',
            'TANK_CAPACITY',
            'POWER',
            'HEAD_CHANGABLE',
            'INHALATION_STYLE',
        ],
        'BOX_MOD' => [
            'BATTERIES',
            'POWER'
        ],
        'BOX_MOD_CELL' => [
            'BATTERIES',
            'POWER'
        ],
        'SQUONKER_BOX' => [
            'BATTERIES',
            'POWER'
        ],
        'CLEAROMIZER' => [
            'TANK_CAPACITY'
        ],
    ],
];