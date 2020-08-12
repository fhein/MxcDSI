<?php

namespace MxcDropshipIntegrator;

use MxcCommons\Plugin\Service\AugmentedObjectFactory;
use MxcDropshipInnocigs\Models\Model as InnocigsModel;
use MxcDropshipInnocigs\Services\ApiClient as ApiClientInnocigs;
use MxcDropshipInnocigs\Services\ApiClientSequential as ApiClientSequentialInnocigs;
use MxcDropshipInnocigs\Services\ArticleRegistry as ArticleRegistryInnocigs;
use MxcDropshipInnocigs\Services\Credentials as CredentialsInnoCigs;
use MxcDropshipInnocigs\Services\DropshipOrder as DropshipOrderInnocigs;
use MxcDropshipInnocigs\Services\ImportClient as ImportClientInnocigs;

use MxcDropshipIntegrator\Workflow\DocumentRenderer;
use MxcCommons\Toolbox\Shopware\MailTool;
use MxcDropshipIntegrator\Dropship\SupplierRegistry;
use MxcDropshipIntegrator\Excel\ExcelExport;
use MxcDropshipIntegrator\Excel\ExcelImport;
use MxcDropshipIntegrator\Excel\ExcelImportFactory;
use MxcDropshipIntegrator\Excel\ExcelProductImport;
use MxcDropshipIntegrator\Excel\ExportEcigMetaData;
use MxcDropshipIntegrator\Excel\ExportNewProducts;
use MxcDropshipIntegrator\Excel\ExportPriceIssues;
use MxcDropshipIntegrator\Excel\ExportPrices;
use MxcDropshipIntegrator\Excel\ImportPrices;
use MxcDropshipIntegrator\Listener\MappingFilePersister;
use MxcDropshipIntegrator\Mapping\Check\NameMappingConsistency;
use MxcDropshipIntegrator\Mapping\Check\RegularExpressions;
use MxcDropshipIntegrator\Mapping\Check\VariantMappingConsistency;
use MxcDropshipIntegrator\Mapping\Import\AssociatedProductsMapper;
use MxcDropshipIntegrator\Mapping\Import\CapacityMapper;
use MxcDropshipIntegrator\Mapping\Import\CategoryMapper;
use MxcDropshipIntegrator\Mapping\Import\CommonNameMapper;
use MxcDropshipIntegrator\Mapping\Import\DescriptionMapper;
use MxcDropshipIntegrator\Mapping\Import\DosageMapper;
use MxcDropshipIntegrator\Mapping\Import\FlavorMapper;
use MxcDropshipIntegrator\Mapping\Import\ImportPiecesPerPackMapper;
use MxcDropshipIntegrator\Mapping\Import\ManufacturerMapper;
use MxcDropshipIntegrator\Mapping\Import\MappingConfigFactory;
use MxcDropshipIntegrator\Mapping\Import\NameMapper;
use MxcDropshipIntegrator\Mapping\Import\ProductMappings;
use MxcDropshipIntegrator\Mapping\Import\ProductNumberMapper;
use MxcDropshipIntegrator\Mapping\Import\ProductSeoMapper;
use MxcDropshipIntegrator\Mapping\Import\PropertyMapper;
use MxcDropshipIntegrator\Mapping\Import\TypeMapper;
use MxcDropshipIntegrator\Mapping\Import\VariantNumberMapper;
use MxcDropshipIntegrator\Mapping\ImportMapper;
use MxcDropshipIntegrator\Mapping\ImportPriceMapper;
use MxcDropshipIntegrator\Mapping\MetaData\MetaDataExtractor;
use MxcDropshipIntegrator\Mapping\ProductMapper;
use MxcDropshipIntegrator\Mapping\Pullback\DescriptionPullback;
use MxcDropshipIntegrator\Mapping\Pullback\SpellChecker;
use MxcDropshipIntegrator\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipIntegrator\Mapping\Shopware\CategoryMapper as ShopwareCategoryMapper;
use MxcDropshipIntegrator\Mapping\Shopware\DetailMapper;
use MxcDropshipIntegrator\Mapping\Shopware\DropshippersCompanion;
use MxcDropshipIntegrator\Mapping\Shopware\ImageMapper;
use MxcDropshipIntegrator\Mapping\Shopware\OptionMapper;
use MxcDropshipIntegrator\Mapping\Shopware\PriceEngine;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcDropshipIntegrator\Models\Category;
use MxcDropshipIntegrator\Models\Group;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\Report\ArrayReport;
use MxcCommons\Toolbox\Html\HtmlDocument;
use MxcCommons\Toolbox\Regex\RegexChecker;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcCommons\Toolbox\Shopware\CategoryTool;
use MxcCommons\Toolbox\Shopware\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcCommons\Toolbox\Shopware\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcCommons\Toolbox\Shopware\Filter\GroupRepository as FilterGroupRepository;
use MxcCommons\Toolbox\Shopware\MediaTool;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

return [
    'dropship' => [
        'suppliers' => [
            'InnoCigs',
        ],
    ],
    'plugin'   => [
        MappingFilePersister::class,
    ],
    'doctrine' => [
        'models'     => [
            Category::class,
            Group::class,
            Option::class,
            Product::class,
            Variant::class,
            InnocigsModel::class, // @todo: Move to MxcDropshipInnocigs
        ],
        'attributes' => [
            // @todo: Move to MxcDropshipInnocigs
            's_order_attributes'         => [
                'mxc_dsi_innocigs' => ['type' => TypeMapping::TYPE_INTEGER],
            ],
            's_order_details_attributes' => [
                'mxc_dsi_innocigs' => ['type' => TypeMapping::TYPE_INTEGER],
            ],
            's_articles_attributes'      => [
                'mxc_dsi_ic_registered'     => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxc_dsi_ic_status'         => ['type' => TypeMapping::TYPE_INTEGER],
                'mxc_dsi_ic_active'         => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxc_dsi_ic_preferownstock' => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxc_dsi_ic_productnumber'  => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_productname'    => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_purchaseprice'  => ['type' => TypeMapping::TYPE_FLOAT],
                'mxc_dsi_ic_retailprice'    => ['type' => TypeMapping::TYPE_FLOAT],
                'mxc_dsi_ic_instock'        => ['type' => TypeMapping::TYPE_INTEGER],

                'mxc_product_type' => ['type' => TypeMapping::TYPE_STRING],
                'mxc_product_meta' => ['type' => TypeMapping::TYPE_INTEGER],
            ],
        ],
    ],

    'services'     => [
        'factories' => [
            PriceEngine::class               => AugmentedObjectFactory::class,
            ExportPriceIssues::class         => AugmentedObjectFactory::class,
            ProductMappings::class           => AugmentedObjectFactory::class,
            ImportPiecesPerPackMapper::class => AugmentedObjectFactory::class,
            ProductNumberMapper::class       => AugmentedObjectFactory::class,
            TypeMapper::class                => AugmentedObjectFactory::class,
            VariantNumberMapper::class       => AugmentedObjectFactory::class,
            AssociatedProductsMapper::class  => AugmentedObjectFactory::class,
            AssociatedArticlesMapper::class  => AugmentedObjectFactory::class,
            VariantMappingConsistency::class => AugmentedObjectFactory::class,
            ImportPriceMapper::class         => AugmentedObjectFactory::class,
            DocumentRenderer::class          => AugmentedObjectFactory::class,
            MailTool::class              => AugmentedObjectFactory::class,
            DropshipOrderInnocigs::class     => AugmentedObjectFactory::class,
            SupplierRegistry::class          => AugmentedObjectFactory::class,

            ArticleTool::class                 => AugmentedObjectFactory::class,
            ConfiguratorGroupRepository::class => AugmentedObjectFactory::class,
            ConfiguratorSetRepository::class   => AugmentedObjectFactory::class,
            DescriptionPullback::class         => AugmentedObjectFactory::class,
            SpellChecker::class                => AugmentedObjectFactory::class,
            FilterGroupRepository::class       => AugmentedObjectFactory::class,
            MappingFilePersister::class        => AugmentedObjectFactory::class,
            MediaTool::class                   => AugmentedObjectFactory::class,

            CategoryTool::class => AugmentedObjectFactory::class,

            CommonNameMapper::class  => MappingConfigFactory::class,
            ProductSeoMapper::class  => AugmentedObjectFactory::class,
            DosageMapper::class      => MappingConfigFactory::class,
            CapacityMapper::class    => MappingConfigFactory::class,
            FlavorMapper::class      => MappingConfigFactory::class,
            NameMapper::class        => MappingConfigFactory::class,
            CategoryMapper::class    => MappingConfigFactory::class,
            DescriptionMapper::class => MappingConfigFactory::class,

            ExportNewProducts::class => AugmentedObjectFactory::class,

            ExcelProductImport::class => ExcelImportFactory::class,

        ],
        'magicals'  => [
            ArticleRegistryInnocigs::class,
            ApiClientInnocigs::class,
            ApiClientSequentialInnocigs::class,
            ArrayReport::class,
            ShopwareCategoryMapper::class,
            ConfiguratorSetRepository::class,
            CredentialsInnocigs::class,
            DetailMapper::class,
            DropshippersCompanion::class,
            ExcelExport::class,
            ExcelImport::class,
            ExportPrices::class,
            FilterTest::class,
            ImageMapper::class,
            ImportClientInnocigs::class,
            ImportMapper::class,
            ImportPrices::class,
            ManufacturerMapper::class,
            MetaDataExtractor::class,
            NameMappingConsistency::class,
            OptionMapper::class,
            PriceMapper::class,
            ProductMapper::class,
            PropertyMapper::class,
            RegexChecker::class,
            RegularExpressions::class,
            HtmlDocument::class,
        ],
    ],
    'class_config' => [
        AssociatedProductsMapper::class => 'AssociatedProductsMapper.config.php',
        CategoryMapper::class           => 'CategoryMapper.config.php',
        DescriptionMapper::class        => 'DescriptionMapper.config.php',
        CommonNameMapper::class         => 'CommonNameMapper.config.php',
        FlavorMapper::class             => 'FlavorMapper.config.php',
        ImportClient::class             => 'ImportClient.config.php',
        ManufacturerMapper::class       => 'ManufacturerMapper.config.php',
        NameMapper::class               => 'NameMapper.config.php',
        ProductMappings::class          => 'ProductMappings.config.phpx',
        ProductNumberMapper::class      => 'ProductNumberMapper.config.php',
        PropertyMapper::class           => 'PropertyMapper.config.php',
        TypeMapper::class               => 'TypeMapper.config.php',
        VariantNumberMapper::class      => 'VariantNumberMapper.config.php',
        ProductSeoMapper::class         => 'ProductSeoMapper.config.php',
        ShopwareCategoryMapper::class   => 'CategoryMapper.config.php',
        SpellChecker::class             => 'SpellChecker.config.php',
        PriceEngine::class              => 'PriceEngine.config.php',
        MetaDataExtractor::class        => 'MetaDataExtractor.config.php',
        DropshipOrder::class            => 'DropshipOrder.config.php',
        SupplierRegistry::class         => 'SupplierRegistry.config.php',
    ],
    'excel'        => [
        'import' => [
            'Preise' => ImportPrices::class,
        ],
        'export' => [
            'Prices'         => [
//              'Neue Produkte' => ExportNewProducts::class,
'Preise' => ExportPrices::class,
            ],
            'Price Issues'   => [
                'Preisprobleme' => ExportPriceIssues::class,
            ],
            'Ecig Meta Data' => [
                'Metadata' => ExportEcigMetaData::class,
            ],
        ],
    ],
];
