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
use MxcDropshipInnocigs\Models\Import\ImportArticle;
use MxcDropshipInnocigs\Models\Import\ImportGroup;
use MxcDropshipInnocigs\Models\Import\ImportImage;
use MxcDropshipInnocigs\Models\Import\ImportOption;
use MxcDropshipInnocigs\Models\Import\ImportVariant;
use MxcDropshipInnocigs\Toolbox\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcDropshipInnocigs\Toolbox\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcDropshipInnocigs\Toolbox\Filter\GroupRepository as FilterGroupRepository;
use MxcDropshipInnocigs\Toolbox\Media\MediaTool;

return [
    'plugin' => [
        FilterTest::class => [
            'options' => [
                'activate' => [],
                'deactivate' => [],
            ],
        ],
        DumpOnUninstall::class => [],
        ArticleAttributeFilePersister::class => [
            'innocigsBrands' => [
                'SC',
                'Steamax',
                'InnoCigs',
                'Akkus'
            ],
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
            ImportArticle::class,
            ImportVariant::class,
            ImportGroup::class,
            ImportOption::class,
            ImportImage::class,
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
//        'listeners' => [
//            InnocigsArticleSubscriber::class => [
//                'model' => ImportArticle::class,
//                'events' => [
//                    Events::preUpdate,
//                ],
//            ],
//        ]
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
        'arcticle_names'    => [],
        'group_names' => [
            'STAERKE'       => 'Nikotinstärke',
            'WIDERSTAND'    => 'Widerstand',
            'PACKUNG'       => 'Packungsgröße',
            'FARBE'         => 'Farbe',
            'DURCHMESSER'   => 'Durchmesser',
            'GLAS'          => 'Glas',
        ],
        'option_names'      => [],
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
            'numberOfArticles' => -1,
            'applyFilters' => true,
        ],
        ImportClient::class => [
            'numberOfArticles' => -1,
        ]
    ],
];
