<?php

namespace MxcDropshipInnocigs;

use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Client\Credentials;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Import\ImportModifier;
use MxcDropshipInnocigs\Import\InnocigsUpdater;
use MxcDropshipInnocigs\Import\PropertyMapper;
use MxcDropshipInnocigs\Listener\ArticleAttributeFilePersister;
use MxcDropshipInnocigs\Listener\DumpOnUninstall;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapper;
use MxcDropshipInnocigs\Mapping\InnocigsEntityValidator;
use MxcDropshipInnocigs\Models\Current\Article;
use MxcDropshipInnocigs\Models\Current\Group;
use MxcDropshipInnocigs\Models\Current\Image;
use MxcDropshipInnocigs\Models\Current\Option;
use MxcDropshipInnocigs\Models\Current\Variant;
use MxcDropshipInnocigs\Models\Import\Model;
use MxcDropshipInnocigs\Subscriber\ModelSubscriber;
use MxcDropshipInnocigs\Toolbox\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcDropshipInnocigs\Toolbox\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcDropshipInnocigs\Toolbox\Filter\GroupRepository as FilterGroupRepository;
use MxcDropshipInnocigs\Toolbox\Media\MediaTool;

return [
    'plugin' => [
//        FilterTest::class => [
//            'options' => [
//                'activate' => [],
//                'deactivate' => [],
//            ],
//        ],
        DumpOnUninstall::class => [],
        ArticleAttributeFilePersister::class => [
            'articleConfigFile' => __DIR__ . '/../Config/article.config.php',
        ],
    ],
    'doctrine' => [
        'models' => [
            Article::class,
            Variant::class,
            Group::class,
            Option::class,
            Image::class,
            Model::class,
        ],
        'attributes' => [
            's_articles_attributes' => [
                'mxc_dsi_supplier' => [
                    'type' => 'string',
                    'settings' => [
                        'label'            => '',
                        'supportText'      => '',
                        'helpText'         => '',
                        'translatable'     => false,
                        'displayInBackend' => false,
                        'position'         => 10000,
                        'custom'           => false
                    ]
                ],
                'mxc_dsi_brand' => [
                    'type' => 'string',
                    'settings' => [
                        'label'            => '',
                        'supportText'      => '',
                        'helpText'         => '',
                        'translatable'     => false,
                        'displayInBackend' => false,
                        'position'         => 10100,
                        'custom'           => false
                    ],
                ],
                'dc_ic_ordernumber' => [
                    'type' => 'string',
                ],
                'dc_ic_articlename' => [
                    'type' => 'string',
                ],
                'dc_ic_purchasing_price' => [
                    'type' => 'string',
                ],
                'dc_ic_retail_price' => [
                    'type' => 'string',
                ],
                'dc_ic_instock' => [
                    'type' => 'integer',
                ],
                'dc_ic_active' => [
                    'type' => 'boolean',
                ],
            ],
        ],
        'listeners' => [
//            ModelSubscriber::class => [
//                'model' => Model::class,
//                'events' => [
//                    Events::postPersist,
//                    Events::preUpdate,
//                ],
//            ],
        ]
    ],
    'filters' => [

    ],
    'services' => [
        'magicals' => [
            ApiClient::class,
            ArticleOptionMapper::class,
            ArticleMapper::class,
            Credentials::class,
            ConfiguratorGroupRepository::class,
            ConfiguratorSetRepository::class,
            FilterGroupRepository::class,
            ImportClient::class,
            ImportMapper::class,
            FilterTest::class,
            PropertyMapper::class,
            FilterGroupRepository::class,
            MediaTool::class,
            InnocigsUpdater::class,
            InnocigsEntityValidator::class,
            ImportModifier::class,
            ArticleAttributeFilePersister::class,
            DumpOnUninstall::class,
        ],
    ],
    'mappings' => [
        'article_codes'     => [],
        'article_names'    => [
            'Vampire Vape Applelicious - E-Zigaretten Liquid' => 'Vampire Vape - Applelicious - E-Zigaretten Liquid',

            'Chubby Gorilla 30ML LDPE Unicorn Leerflasche' => 'Chubby Gorilla - LPDE Unicorn Leerflasche - 30 ml',
            'Chubby Gorilla 60ML LDPE Unicorn Leerflasche' => 'Chubby Gorilla - LPDE Unicorn Leerflasche - 60 ml',

            'Chubby Gorilla 10ML V3 PET Unicorn Leerflasche' => 'Chubby Gorilla - V3 PET Unicorn Leerflasche - 10 ml',
            'Chubby Gorilla 30ML V3 PET Unicorn Leerflasche' => 'Chubby Gorilla - V3 PET Unicorn Leerflasche - 30 ml',
            'Chubby Gorilla 60ML V3 PET Unicorn Leerflasche' => 'Chubby Gorilla - V3 PET Unicorn Leerflasche - 60 ml',
            'Chubby Gorilla 100ML V3 PET Unicorn Leerflasche' => 'Chubby Gorilla - V3 PET Unicorn Leerflasche - 100 ml',
            'Chubby Gorilla 120ML V3 PET Unicorn Leerflasche' => 'Chubby Gorilla - V3 PET Unicorn Leerflasche - 120 ml',
            'Chubby Gorilla 200ML V3 PET Unicorn Leerflasche' => 'Chubby Gorilla - V3 PET Unicorn Leerflasche - 200 ml',

            'Chubby Gorilla 30ML Stubby PET Unicorn Leerflasche' => 'Chubby Gorilla - Stubby PET Unicorn Leerflasche - 30 ml',
            'Chubby Gorilla 75ML Stubby PET Unicorn Leerflasche' => 'Chubby Gorilla - Stubby PET Unicorn Leerflasche - 75 ml',
        ],
        'article_name_parts' => [
            '50ml' => '50 ml',
            '100ml' => '100 ml',
            '10ml' => '10 ml',
            '40ml' => '40 ml',
            '25ml' => '25 ml',
            '15ml' => '- 15 ml',
            '4ml' => '- 4 ml',
            '- 50 ml, 0mg' => '- 50 ml, 0 mg/ml',
            '40 ml - 0 mg' => '40 ml, 0 mg/ml',
            '50 ml - 0 mg' => '- 50 ml, 0 mg/ml',
            '0mg/ml' => '0 mg/ml',
            'mAh 40A' => 'mAh, 40 A',
            '50VG/50PG' => 'PG/VG 50:50,',
            '50PG/50VG' => 'PG/VG 50:50,',
            '30ML' => '30 ml',
            '0 mg/ml 40 ml' => '- 40 ml, 0 mg/ml',
            '0 mg/ml 100 ml' => '- 100 ml, 0 mg/ml',
            '0 mg/ml 50 ml' => '- 50 ml, 0 mg/ml',
            '50 ml - 0 mg/ml' => '- 50 ml, 0 mg/ml',
            '100 ml - 0 mg/ml' => '- 100 ml, 0 mg/ml',
            'AsMODus' => 'asMODus',
            'pro Pack)' => 'pro Packung)',
            'St. pro' => 'Stück pro',
            '10 ml' => '- 10 ml',
            'x - 10' => 'x 10',
            '- -' => '-',
            'ml 0' => 'ml, 0',
            '/ml/ml' => '/ml',
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
        ]
    ],
    'import' => [
        'update' => [
            [
                'entity' => Article::class,
                'andWhere' => [
                    [
                        'field' => 'name',
                        'operator' => 'LIKE',
                        'value' => '%iquid%'
                    ]
                ],
                'set' => [
                    'accepted' => false,
                    'active' => false,
                ]
            ],
            [
                'entity' => Article::class,
                'andWhere' => [
                    [
                        'field' => 'name',
                        'operator' => 'LIKE',
                        'value' => '%Aroma%'
                    ]
                ],
                'set' => [
                    'accepted' => false,
                    'active' => false,
                ]
            ],
            [
                'entity' => Article::class,
                'andWhere' => [
                    [
                        'field' => 'brand',
                        'operator' => 'LIKE',
                        'value' => 'DVTCH Amsterdam'
                    ]
                ],
                'set' => [
                    'accepted' => false,
                    'active' => false,
                ]
            ],
        ],
    ],
    'class_config' => [
        ImportMapper::class => [
            'numberOfArticles' => 100,
            'applyFilters' => true,
        ],
        ImportClient::class => [
            'numberOfArticles' => 100,
        ],
    ],
];
