<?php

namespace MxcDropshipInnocigs;

use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Import\Credentials;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as PropertyMapperReport;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Listener\MappingFilePersister;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Csv\ArticlePrices;
use MxcDropshipInnocigs\Mapping\EntityValidator;
use MxcDropshipInnocigs\Mapping\Import\ClassConfigFactory;
use MxcDropshipInnocigs\Mapping\Import\Flavorist;
use MxcDropshipInnocigs\Mapping\Import\ImportArticleCodeBaseImportMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportAssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportCategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportCommonNameMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportDosageMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportFlavorMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportManufacturerMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportMappings;
use MxcDropshipInnocigs\Mapping\Import\ImportNameMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportPiecesPerPackMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportPropertyMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportTypeMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportVariantCodeBaseImportMapper;
use MxcDropshipInnocigs\Mapping\Import\MappingConfigFactory;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareAssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareOptionMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwarePriceMapper;
use MxcDropshipInnocigs\Mapping\ShopwareMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Image;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Subscriber\ModelSubscriber;
use MxcDropshipInnocigs\Toolbox\Regex\RegexChecker;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Filter\GroupRepository as FilterGroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;

return [
    'plugin'       => [
        MappingFilePersister::class,
    ],
    'doctrine'     => [
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
    'services'     => [

        'factories' => [
            ImportArticleCodeBaseImportMapper::class => ClassConfigFactory::class,
            ImportCategoryMapper::class              => ClassConfigFactory::class,
            ImportCommonNameMapper::class            => ClassConfigFactory::class,
            ImportMappings::class                    => ClassConfigFactory::class,
            ImportNameMapper::class                  => ClassConfigFactory::class,
            ImportPiecesPerPackMapper::class         => ClassConfigFactory::class,
            ImportTypeMapper::class                  => ClassConfigFactory::class,
            ImportVariantCodeBaseImportMapper::class => ClassConfigFactory::class,

            ImportDosageMapper::class => MappingConfigFactory::class,
            ImportFlavorMapper::class => MappingConfigFactory::class,
        ],
        'magicals'  => [
            ApiClient::class,
            ArrayReport::class,
            ArticlePrices::class,
            ShopwareCategoryMapper::class,
            CategoryTool::class,
            ConfiguratorGroupRepository::class,
            ConfiguratorSetRepository::class,
            Credentials::class,
            FilterGroupRepository::class,
            FilterTest::class,
            Flavorist::class,
            ShopwareImageMapper::class,
            ImportAssociatedArticlesMapper::class,
            ImportClient::class,
            ImportManufacturerMapper::class,
            ImportMapper::class,
            ImportPropertyMapper::class,
            MappingFilePersister::class,
            MediaTool::class,
            NameMappingConsistency::class,
            ShopwarePriceMapper::class,
            PropertyMapperReport::class,
            RegexChecker::class,
            RegularExpressions::class,
            ShopwareMapper::class,
            ShopwareAssociatedArticlesMapper::class,
            ShopwareOptionMapper::class,
        ],
    ],
    'class_config' => [
        ImportMappings::class                 => 'ImportMappings.config.php',
        ImportCategoryMapper::class           => 'ImportCategoryMapper.config.php',
        ImportArticleCodeMapper::class        => 'ImportArticleCodeMapper.config.php',
        ImportCommonNameMapper::class         => 'ImportCommonNameMapper.config.php',
        ImportManufacturerMapper::class       => 'ImportManufacturerMapper.config.php',
        ImportNameMapper::class               => 'ImportNameMapper.config.php',
        ImportTypeMapper::class               => 'ImportTypeMapper.config.php',
        ImportAssociatedArticlesMapper::class => 'ImportAssociatedArticlesMapper.php',
        ImportClient::class                   => 'ImportClient.config.php',
        ImportMapper::class                   => 'ImportMapper.config.php',
        ImportPropertyMapper::class           => 'ImportPropertyMapper.config.php',
        ImportVariantCodeMapper::class        => 'ImportVariantCodeMapper.config.php',

        ShopwareMapper::class       => [
            'root_category' => 'Deutsch',
        ],
        MappingFilePersister::class => [
            'mappingsFile' => __DIR__ . '/../Config/ImportMappings.config.php',
        ],
    ],
];
