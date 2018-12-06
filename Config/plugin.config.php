<?php

namespace MxcDropshipInnocigs;

use Doctrine\ORM\Events;
use Mxc\Shopware\Plugin\Database\Database;
use Mxc\Shopware\Plugin\Database\DatabaseFactory;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Client\ApiClientFactory;
use MxcDropshipInnocigs\Client\Credentials;
use MxcDropshipInnocigs\Client\CredentialsFactory;
use MxcDropshipInnocigs\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcDropshipInnocigs\Configurator\GroupRepositoryFactory as ConfiguratorGroupRepositoryFactory;
use MxcDropshipInnocigs\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcDropshipInnocigs\Configurator\SetRepositoryFactory as ConfiguratorSetRepositoryFactory;
use MxcDropshipInnocigs\Filter\GroupRepository as FilterGroupRepository;
use MxcDropshipInnocigs\Filter\GroupRepositoryFactory as FilterGroupRepositoryFactory;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Listener\FilterTestFactory;
use MxcDropshipInnocigs\Listener\InnocigsClient;
use MxcDropshipInnocigs\Listener\InnocigsClientFactory;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleMapperFactory;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapper;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapperFactory;
use MxcDropshipInnocigs\Mapping\PropertyMapper;
use MxcDropshipInnocigs\Mapping\PropertyMapperFactory;
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
        'factories' => [
            ApiClient::class                    => ApiClientFactory::class,
            ArticleOptionMapper::class          => ArticleOptionMapperFactory::class,
            ArticleMapper::class                => ArticleMapperFactory::class,
            Credentials::class                  => CredentialsFactory::class,
            Database::class                     => DatabaseFactory::class,
            ConfiguratorGroupRepository::class  => ConfiguratorGroupRepositoryFactory::class,
            ConfiguratorSetRepository::class    => ConfiguratorSetRepositoryFactory::class,
            FilterGroupRepository::class        => FilterGroupRepositoryFactory::class,
            InnocigsClient::class               => InnocigsClientFactory::class,
            FilterTest::class                   => FilterTestFactory::class,
            PropertyMapper::class               => PropertyMapperFactory::class,
            PropertyRepository::class           => PropertyRepositoryFactory::class,
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
