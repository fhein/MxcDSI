<?php

namespace MxcDropshipInnocigs;

use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Import\Credentials;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as PropertyMapperReport;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Listener\MappingFilePersister;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Csv\ArticlePrices;
use MxcDropshipInnocigs\Mapping\EntityValidator;
use MxcDropshipInnocigs\Mapping\Import\AromaDosageMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleCategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleCodeMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleCommonNameMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleFlavorMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleManufacturerMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleNameMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticlePiecesPerPackMapper;
use MxcDropshipInnocigs\Mapping\Import\ArticleTypeMapper;
use MxcDropshipInnocigs\Mapping\Import\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Import\Flavorist;
use MxcDropshipInnocigs\Mapping\Import\ImportMapperFactory;
use MxcDropshipInnocigs\Mapping\Import\VariantCodeMapper;
use MxcDropshipInnocigs\Mapping\ImportPropertyMapper;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Mapping\ShopwareArticleMapper;
use MxcDropshipInnocigs\Mapping\ShopwareOptionMapper;
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

        'factories' => [
            AromaDosageMapper::class          => ImportMapperFactory::class,
            ArticleCategoryMapper::class      => ImportMapperFactory::class,
            ArticleCodeMapper::class          => ImportMapperFactory::class,
            ArticleCommonNameMapper::class    => ImportMapperFactory::class,
            ArticleFlavorMapper::class        => ImportMapperFactory::class,
            ArticleManufacturerMapper::class  => ImportMapperFactory::class,
            ArticleNameMapper::class          => ImportMapperFactory::class,
            ArticlePiecesPerPackMapper::class => ImportMapperFactory::class,
            ArticleTypeMapper::class          => ImportMapperFactory::class,
            VariantCodeMapper::class          => ImportMapperFactory::class,
        ],
        'magicals'  => [
            ApiClient::class,
            ArrayReport::class,
            ShopwareArticleMapper::class,
            ShopwareOptionMapper::class,
            ArticlePrices::class,
            AssociatedArticlesMapper::class,
            ConfiguratorGroupRepository::class,
            ConfiguratorSetRepository::class,
            Credentials::class,
            FilterGroupRepository::class,
            FilterTest::class,
            Flavorist::class,
            ImportClient::class,
            ImportMapper::class,
            MappingFilePersister::class,
            MediaTool::class,
            NameMappingConsistency::class,
            PriceMapper::class,
            ImportPropertyMapper::class,
            PropertyMapperReport::class,
            RegexChecker::class,
            RegularExpressions::class,
        ],
    ],
    // @todo: multiple includes of the same file
    'class_config' => [
        AromaDosageMapper::class         => include __DIR__ . '/article.config.php',
        ArticleCategoryMapper::class     => include __DIR__ . '/ArticleCategoryMapper.config.php',
        ArticleCodeMapper::class         => include __DIR__ . '/ArticleCodeMapper.config.php',
        ArticleCommonNameMapper::class   => include __DIR__ . '/ArticleCommonNameMapper.config.php',
        ArticleFlavorMapper::class       => include __DIR__ . '/article.config.php',
        ArticleManufacturerMapper::class => include __DIR__ . '/ArticleManufacturerMapper.config.php',
        ArticleNameMapper::class         => include __DIR__ . '/ArticleNameMapper.config.php',
        ArticleTypeMapper::class         => include __DIR__ . '/ArticleTypeMapper.config.php',
        AssociatedArticlesMapper::class  => include __DIR__ . '/AssociatedArticlesMapper.php',
        ImportClient::class              => include __DIR__ . '/ImportClient.config.php',
        ImportMapper::class              => include __DIR__ . '/ImportMapper.config.php',
        ImportPropertyMapper::class      => include __DIR__ . '/ImportPropertyMapper.config.php',
        VariantCodeMapper::class         => include __DIR__ . '/VariantCodeMapper.config.php',

        ShopwareArticleMapper::class => [
            'root_category' => 'Deutsch',
        ],
        MappingFilePersister::class  => [
            'articleConfigFile' => __DIR__ . '/../Config/article.config.php',
        ],
    ],
];
