<?php

namespace MxcDropshipInnocigs;

use Doctrine\ORM\Events;
use Mxc\Shopware\Plugin\Database\Database;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Client\Credentials;
use MxcDropshipInnocigs\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcDropshipInnocigs\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcDropshipInnocigs\Filter\GroupRepository as FilterGroupRepository;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Listener\InnocigsClient;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapper;
use MxcDropshipInnocigs\Mapping\PropertyMapper;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsGroup;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use MxcDropshipInnocigs\Subscriber\InnocigsArticleSubscriber;

return [
    'plugin' => [
        InnocigsClient::class => [
            'options' => [
                'activate' => [
                    'importArticles' => false,
                    'numberOfArticles' => -1,
                    'clearCache' => false,
                ],
            ],
        ],
        FilterTest::class => [
            'options' => [
                'activate' => [],
                'deactivate' => [],
            ],
        ],
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
                'mxc_ds_test_attribute' => [
                    'type' => 'text',
                    'settings' => [
                        'label'            => '',
                        'supportText'      => '',
                        'helpText'         => '',
                        'translatable'     => false,
                        'displayInBackend' => false,
                        'position'         => 10000,
                        'custom'           => false
                    ],
                    'newColumnName' => null,
                    'updateDependingTables' => false,
                    'defaultValue' => null,
                ]
            ]
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
            Database::class,
            ConfiguratorGroupRepository::class,
            ConfiguratorSetRepository::class,
            FilterGroupRepository::class,
            InnocigsClient::class,
            FilterTest::class,
            PropertyMapper::class,
            FilterGroupRepository::class,
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
];
