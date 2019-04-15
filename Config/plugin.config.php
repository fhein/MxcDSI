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
use MxcDropshipInnocigs\Mapping\Csv\ProductPrices;
use MxcDropshipInnocigs\Mapping\EntityValidator;
use MxcDropshipInnocigs\Mapping\Gui\ProductUpdater;
use MxcDropshipInnocigs\Mapping\Import\AssociatedProductsMapper;
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\ClassConfigFactory;
use MxcDropshipInnocigs\Mapping\Import\CommonNameMapper;
use MxcDropshipInnocigs\Mapping\Import\DosageMapper;
use MxcDropshipInnocigs\Mapping\Import\Flavorist;
use MxcDropshipInnocigs\Mapping\Import\FlavorMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportMappings;
use MxcDropshipInnocigs\Mapping\Import\ImportPiecesPerPackMapper;
use MxcDropshipInnocigs\Mapping\Import\ManufacturerMapper;
use MxcDropshipInnocigs\Mapping\Import\MappingConfigFactory;
use MxcDropshipInnocigs\Mapping\Import\NameMapper;
use MxcDropshipInnocigs\Mapping\Import\ProductNumberMapper;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\Import\TypeMapper;
use MxcDropshipInnocigs\Mapping\Import\VariantNumberMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ArticleCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ArticleImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ArticlePriceMapper;
use MxcDropshipInnocigs\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ConfiguratorOptionMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DropshippersCompanion;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Image;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Subscriber\ModelSubscriber;
use MxcDropshipInnocigs\Toolbox\Regex\RegexChecker;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
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
            Group::class,
            Image::class,
            Model::class,
            Option::class,
            Product::class,
            Variant::class,
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
            CategoryMapper::class            => ClassConfigFactory::class,
            CommonNameMapper::class          => ClassConfigFactory::class,
            ImportMappings::class            => ClassConfigFactory::class,
            ImportPiecesPerPackMapper::class => ClassConfigFactory::class,
            NameMapper::class                => ClassConfigFactory::class,
            ProductNumberMapper::class       => ClassConfigFactory::class,
            TypeMapper::class                => ClassConfigFactory::class,
            VariantNumberMapper::class       => ClassConfigFactory::class,

            DosageMapper::class => MappingConfigFactory::class,
            FlavorMapper::class => MappingConfigFactory::class,
        ],
        'magicals'  => [
            ApiClient::class,
            ArrayReport::class,
            ArticleCategoryMapper::class,
            ArticleImageMapper::class,
            ArticlePriceMapper::class,
            ArticleTool::class,
            AssociatedArticlesMapper::class,
            AssociatedProductsMapper::class,
            CategoryTool::class,
            ConfiguratorGroupRepository::class,
            ConfiguratorOptionMapper::class,
            ConfiguratorSetRepository::class,
            Credentials::class,
            DetailMapper::class,
            DropshippersCompanion::class,
            FilterGroupRepository::class,
            FilterTest::class,
            Flavorist::class,
            ImportClient::class,
            ImportMapper::class,
            ManufacturerMapper::class,
            MappingFilePersister::class,
            MediaTool::class,
            NameMappingConsistency::class,
            ProductMapper::class,
            ProductPrices::class,
            ProductUpdater::class,
            PropertyMapper::class,
            PropertyMapperReport::class,
            RegexChecker::class,
            RegularExpressions::class,
        ],
    ],
    'class_config' => [
        AssociatedProductsMapper::class => 'AssociatedProductsMapper.config.php',
        CategoryMapper::class           => 'CategoryMapper.config.php',
        CommonNameMapper::class         => 'CommonNameMapper.config.php',
        ImportClient::class             => 'ImportClient.config.php',
        ImportMapper::class             => 'ImportMapper.config.php',
        ImportMappings::class           => 'ImportMappings.config.php',
        ManufacturerMapper::class       => 'ManufacturerMapper.config.php',
        NameMapper::class               => 'NameMapper.config.php',
        ProductNumberMapper::class      => 'ProductNumberMapper.config.php',
        PropertyMapper::class           => 'PropertyMapper.config.php',
        TypeMapper::class               => 'TypeMapper.config.php',
        VariantNumberMapper::class      => 'VariantNumberMapper.config.php',

        ProductMapper::class        => [
            'root_category' => 'Deutsch',
        ],
        MappingFilePersister::class => [
            'mappingsFile' => __DIR__ . '/../Config/ImportMappings.config.php',
        ],
    ],
];
