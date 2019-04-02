<?php

namespace MxcDropshipInnocigs;

use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Import\Credentials;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as PropertyMapperReport;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Listener\MappingFilePersister;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapper;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\EntityValidator;
use MxcDropshipInnocigs\Mapping\Import\ArticleManufacturerMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleNameMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleTypeMapper;
use MxcDropshipInnocigs\Mapping\Import\Flavorist;
use MxcDropshipInnocigs\Mapping\Import\PropertyDerivator;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Image;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Subscriber\ModelSubscriber;
use MxcDropshipInnocigs\Toolbox\Regex\RegexChecker;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Filter\GroupRepository as FilterGroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;

return [
    'plugin'   => [
        MappingFilePersister::class,
    ],
    'doctrine' => [
        'models'     => [
            Article::class,
            Variant::class,
            Group::class,
            Option::class,
            Image::class,
            Model::class,
        ],
        'attributes' => [
            's_articles_attributes' => [
                'mxc_dsi_supplier'       => [
                    'type' => 'string',
                    //                    'settings' => [
                    //                        'label'            => '',
                    //                        'supportText'      => '',
                    //                        'helpText'         => '',
                    //                        'translatable'     => false,
                    //                        'displayInBackend' => false,
                    //                        'position'         => 10000,
                    //                        'custom'           => false
                    //                    ]
                ],
                'mxc_dsi_brand'          => [
                    'type' => 'string',
                ],
                'mxc_dsi_flavor'         => [
                    'type' => 'string',
                ],
                'mxc_dsi_master'         => [
                    'type' => 'string',
                ],
                'mxc_dsi_type'           => [
                    'type' => 'string',
                ],
                'dc_ic_ordernumber'      => [
                    'type' => 'string',
                ],
                'dc_ic_articlename'      => [
                    'type' => 'string',
                ],
                'dc_ic_purchasing_price' => [
                    'type' => 'string',
                ],
                'dc_ic_retail_price'     => [
                    'type' => 'string',
                ],
                'dc_ic_instock'          => [
                    'type' => 'integer',
                ],
                'dc_ic_active'           => [
                    'type' => 'boolean',
                ],
            ],
        ],
    ],
    'services' => [
        'magicals' => [
            ApiClient::class,
            MappingFilePersister::class,
            ArticleMapper::class,
            ArticleNameMapper::class,
            ArticleTypeMapper::class,
            ArticleManufacturerMapper::class,
            ArticleOptionMapper::class,
            ConfiguratorGroupRepository::class,
            ConfiguratorSetRepository::class,
            Credentials::class,
            FilterGroupRepository::class,
            FilterTest::class,
            Flavorist::class,
            ImportClient::class,
            ImportMapper::class,
            MediaTool::class,
            NameMappingConsistency::class,
            PropertyMapper::class,
            PropertyMapperReport::class,
            ArrayReport::class,
            PropertyDerivator::class,
            RegularExpressions::class,
            RegexChecker::class,
        ],
    ],

    'class_config' => [
        ImportClient::class              => include __DIR__ . '/ImportClient.config.php',
        PropertyMapper::class            => include __DIR__ . '/PropertyMapper.config.php',
        ImportMapper::class              => include __DIR__ . '/ImportMapper.config.php',
        PropertyDerivator::class         => include __DIR__ . '/PropertyDerivator.config.php',
        ArticleNameMapper::class         => include __DIR__ . '/ArticleNameMapper.config.php',
        ArticleTypeMapper::class         => include __DIR__ . '/ArticleTypeMapper.config.php',
        ArticleManufacturerMapper::class => include __DIR__ . '/ArticleManufacturerMapper.config.php',
        ArticleMapper::class             => [
            'root_category' => 'Deutsch',
        ],
        MappingFilePersister::class      => [
            'articleConfigFile' => __DIR__ . '/../Config/article.config.php',
        ],
    ],
];
