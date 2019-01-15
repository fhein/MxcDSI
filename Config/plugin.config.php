<?php

namespace MxcDropshipInnocigs;

use Doctrine\ORM\Events;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Client\Credentials;
use MxcDropshipInnocigs\Import\ImportModifier;
use MxcDropshipInnocigs\Import\InnocigsClient;
use MxcDropshipInnocigs\Import\InnocigsUpdater;
use MxcDropshipInnocigs\Listener\ArticleAttributeFilePersister;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapper;
use MxcDropshipInnocigs\Mapping\InnocigsEntityValidator;
use MxcDropshipInnocigs\Mapping\PropertyMapper;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsGroup;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use MxcDropshipInnocigs\Subscriber\InnocigsArticleSubscriber;
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
        ArticleAttributeFilePersister::class => [],
    ],
    'doctrine' => [
        'models' => [
            InnocigsArticle::class,
            InnocigsVariant::class,
            InnocigsGroup::class,
            InnocigsOption::class,
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
            InnocigsArticleSubscriber::class => [
                'model' => InnocigsArticle::class,
                'events' => [
                    Events::preUpdate,
                ],
            ],
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
            InnocigsClient::class,
            FilterTest::class,
            PropertyMapper::class,
            FilterGroupRepository::class,
            MediaTool::class,
            InnocigsUpdater::class,
            InnocigsEntityValidator::class,
            ImportModifier::class,
            ArticleAttributeFilePersister::class,
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
    ],
    'import' => [
        'update' => [
            [
                'entity' => InnocigsArticle::class,
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
                'entity' => InnocigsArticle::class,
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
                'entity' => InnocigsArticle::class,
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
        InnocigsClient::class => [
            'useArticleConfiguration' => true,
            'numberOfArticles' => -1,
            'applyFilters' => true,
        ],
    ],
];
