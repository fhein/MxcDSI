<?php

namespace MxcDropshipInnocigs;

use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Import\Credentials;
use MxcDropshipInnocigs\Import\Flavorist;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Import\PropertyDerivator;
use MxcDropshipInnocigs\Import\PropertyMapper;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as PropertyMapperReport;
use MxcDropshipInnocigs\Listener\ArticleAttributeFilePersister;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\ArticleOptionMapper;
use MxcDropshipInnocigs\Mapping\EntityValidator;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Image;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Subscriber\ModelSubscriber;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Filter\GroupRepository as FilterGroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;

return [
    'plugin'   => [
//        FilterTest::class => [
//            'options' => [
//                'activate' => [],
//                'deactivate' => [],
//            ],
//        ],
        ArticleAttributeFilePersister::class => [
            'articleConfigFile' => __DIR__ . '/../Config/article.config.php',
            'testConfigFile'    => __DIR__ . '/../Config/article.test.config.php'
        ],
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
            ArticleAttributeFilePersister::class,
            ArticleMapper::class,
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
            PropertyMapper::class,
            PropertyMapperReport::class,
            ArrayReport::class,
            PropertyDerivator::class,
        ],
    ],

    'class_config' => [
        ImportClient::class      => include __DIR__ . '/importclient.config.php',
        PropertyMapper::class    => include __DIR__ . '/propertymapper.config.php',
        ImportMapper::class      => include __DIR__ . '/importmapper.config.php',
        PropertyDerivator::class => include __DIR__ . '/propertyderivator.config.php',
        ArticleMapper::class     => [
            'root_category' => 'Deutsch',
        ]
    ],
];
