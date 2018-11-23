<?php

namespace MxcDropshipInnocigs;

use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Bootstrap\Database;
use MxcDropshipInnocigs\Bootstrap\DatabaseFactory;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Client\ApiClientFactory;
use MxcDropshipInnocigs\Client\Credentials;
use MxcDropshipInnocigs\Client\CredentialsFactory;
use MxcDropshipInnocigs\Client\InnocigsClient;
use MxcDropshipInnocigs\Client\InnocigsClientFactory;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleMapperFactory;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapper;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapperFactory;
use MxcDropshipInnocigs\Mapping\GroupRepository;
use MxcDropshipInnocigs\Mapping\GroupRepositoryFactory;
use MxcDropshipInnocigs\Mapping\PropertyMapper;
use MxcDropshipInnocigs\Mapping\PropertyMapperFactory;
use Zend\Log\Formatter\Simple;
use Zend\Log\Logger;

return [
    'plugin' => [
        'application' => [
            Application::class => [
                'onInstall' => [
                    'createSchema' => false,
                ],
                'onActivate' => [
                    'importArticles' => false,
                    'numberOfArticles' => -1,
                    'clearCache' => false,
                ],
                'onDeactivate' => [
                    'dropArticles' => false,
                    'dropConfigurator' => false,
                ],
                'onUninstall' => [
                    'dropSchema' => false
                ]
            ],
        ],
        'listeners' => [

        ],
    ],
    'log' => [
        'writers' => [
            'stream' => [
                'name' => 'stream',
                'priority'  => Logger::ALERT,
                'options'   => [
                    'stream'    => Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs-' . date('Y-m-d') . '.log',
                    'formatter' => [
                        'name'      => Simple::class,
                        'options'   => [
                            'format'            => '%timestamp% %priorityName%: %message% %extra%',
                            'dateTimeFormat'    => 'H:i:s',
                        ],
                    ],
                    'filters' => [
                        'priority' => [
                            'name' => 'priority',
                            'options' => [
                                'operator' => '<=',
                                'priority' => Logger::INFO,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'services' => [
        'factories' => [
            ApiClient::class            => ApiClientFactory::class,
            ArticleOptionMapper::class  => ArticleOptionMapperFactory::class,
            ArticleMapper::class        => ArticleMapperFactory::class,
            Credentials::class          => CredentialsFactory::class,
            Database::class             => DatabaseFactory::class,
            GroupRepository::class      => GroupRepositoryFactory::class,
            InnocigsClient::class       => InnocigsClientFactory::class,
            PropertyMapper::class       => PropertyMapperFactory::class,
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
