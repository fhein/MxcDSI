<?php

namespace MxcDropshipInnocigs;

use Doctrine\DBAL\Connection;
use MxcDropshipInnocigs\Application\AttributeManagerFactory;
use MxcDropshipInnocigs\Application\ConfigurationFactory;
use MxcDropshipInnocigs\Application\DbalConnectionFactory;
use MxcDropshipInnocigs\Application\ModelManagerFactory;
use MxcDropshipInnocigs\Bootstrap\Database;
use MxcDropshipInnocigs\Bootstrap\DatabaseFactory;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Client\ApiClientFactory;
use MxcDropshipInnocigs\Client\Credentials;
use MxcDropshipInnocigs\Client\CredentialsFactory;
use MxcDropshipInnocigs\Client\InnocigsClient;
use MxcDropshipInnocigs\Client\InnocigsClientFactory;
use MxcDropshipInnocigs\Client\PropertyMapper;
use MxcDropshipInnocigs\Client\PropertyMapperFactory;
use MxcDropshipInnocigs\Convenience\ExceptionLogger;
use MxcDropshipInnocigs\Convenience\ExceptionLoggerFactory;
use MxcDropshipInnocigs\Mapping\ArticleAttributeMapper;
use MxcDropshipInnocigs\Mapping\ArticleAttributeMapperFactory;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleMapperFactory;
use MxcDropshipInnocigs\Mapping\GroupRepository;
use MxcDropshipInnocigs\Mapping\GroupRepositoryFactory;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware_Components_Config;
use Zend\Config\Config;
use Zend\Log\Formatter\Simple;
use Zend\Log\Logger;
use Zend\Log\LoggerServiceFactory;

return [
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
            ApiClient::class                    => ApiClientFactory::class,
            ArticleAttributeMapper::class       => ArticleAttributeMapperFactory::class,
            ArticleMapper::class                => ArticleMapperFactory::class,
            Connection::class                   => DbalConnectionFactory::class,
            Credentials::class                  => CredentialsFactory::class,
            CrudService::class                  => AttributeManagerFactory::class,
            Database::class                     => DatabaseFactory::class,
            ExceptionLogger::class              => ExceptionLoggerFactory::class,
            GroupRepository::class              => GroupRepositoryFactory::class,
            InnocigsClient::class               => InnocigsClientFactory::class,
            Logger::class                       => LoggerServiceFactory::class,
            ModelManager::class                 => ModelManagerFactory::class,
            PropertyMapper::class               => PropertyMapperFactory::class,
            Shopware_Components_Config::class   => ConfigurationFactory::class,
        ],
        'aliases' => [
            'logger'                            => Logger::class,
            'exceptionLogger'                   => ExceptionLogger::class,
            'attributeManager'                  => CrudService::class,
            'config'                            => Config::class,
            'dbalConnection'                    => Connection::class,
            'modelManager'                      => ModelManager::class,
            'pluginConfig'                      => Shopware_Components_Config::class,
        ]
    ],
    'mappings' => [
        'group_names' => [
            'STAERKE'       => 'Nikotinstärke',
            'WIDERSTAND'    => 'Widerstand',
            'PACKUNG'       => 'Packungsgröße',
            'FARBE'         => 'Farbe',
            'DURCHMESSER'   => 'Durchmesser',
            'GLAS'          => 'Glas',
        ],
        'arcticle_names'    => [],
        'option_names'      => [],
        'article_codes'     => [],
        'variant_codes'     => [],
    ],
];
