<?php

namespace MxcDropshipIntegrator;

use MxcDropshipIntegrator\Mapping\Import\BulkSupportMapper;
use MxcDropshipIntegrator\Mapping\Import\IgnoredOptionRemover;
use MxcDropshipIntegrator\Mapping\ImportClient;
use MxcDropshipIntegrator\Mapping\ImportClientFactory;
use MxcDropshipIntegrator\Mapping\Shopware\BulkPriceMapper;
use MxcDropshipIntegrator\Models\Model;
use MxcCommons\Toolbox\Shopware\MailTool;
use MxcDropshipIntegrator\Excel\ExcelExport;
use MxcDropshipIntegrator\Excel\ExcelImport;
use MxcDropshipIntegrator\Excel\ExcelImportFactory;
use MxcDropshipIntegrator\Excel\ExcelProductImport;
use MxcDropshipIntegrator\Excel\ExportEcigMetaData;
use MxcDropshipIntegrator\Excel\ExportNewProducts;
use MxcDropshipIntegrator\Excel\ExportPriceIssues;
use MxcDropshipIntegrator\Excel\ExportPrices;
use MxcDropshipIntegrator\Excel\ImportPrices;
use MxcDropshipIntegrator\PluginListeners\MappingFilePersister;
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
use MxcDropshipIntegrator\Mapping\Shopware\ImageMapper;
use MxcDropshipIntegrator\Mapping\Shopware\OptionMapper;
use MxcDropshipIntegrator\Mapping\Shopware\PriceEngine;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcDropshipIntegrator\Models\Category;
use MxcDropshipIntegrator\Models\Group;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Report\ArrayReport;
use MxcCommons\Toolbox\Html\HtmlDocument;
use MxcCommons\Toolbox\Regex\RegexChecker;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcCommons\Toolbox\Shopware\CategoryTool;
use MxcCommons\Toolbox\Shopware\Configurator\GroupRepository as ConfiguratorGroupRepository;
use MxcCommons\Toolbox\Shopware\Configurator\SetRepository as ConfiguratorSetRepository;
use MxcCommons\Toolbox\Shopware\Filter\GroupRepository as FilterGroupRepository;
use MxcCommons\Toolbox\Shopware\MediaTool;

return [
    'plugin_listeners'   => [
        MappingFilePersister::class,
    ],
    'doctrine' => [
        'models'     => [
            Category::class,
            Group::class,
            Option::class,
            Product::class,
            Variant::class,
            Model::class,
        ],
    ],

    'services'       => [
        'factories' => [
            CommonNameMapper::class  => MappingConfigFactory::class,
            DosageMapper::class      => MappingConfigFactory::class,
            CapacityMapper::class    => MappingConfigFactory::class,
            FlavorMapper::class      => MappingConfigFactory::class,
            NameMapper::class        => MappingConfigFactory::class,
            CategoryMapper::class    => MappingConfigFactory::class,
            DescriptionMapper::class => MappingConfigFactory::class,

            ExcelProductImport::class => ExcelImportFactory::class,

        ],
        'magicals'  => [
            BulkPriceMapper::class,
            IgnoredOptionRemover::class,
            BulkSupportMapper::class,
            ProductSeoMapper::class,
            ExportNewProducts::class,
            PriceEngine::class,
            ExportPriceIssues::class,
            ProductMappings::class,
            ImportPiecesPerPackMapper::class,
            ProductNumberMapper::class,
            TypeMapper::class,
            VariantNumberMapper::class,
            AssociatedProductsMapper::class,
            AssociatedArticlesMapper::class,
            VariantMappingConsistency::class,
            ImportPriceMapper::class,
            MailTool::class,
            ArticleTool::class,
            ConfiguratorGroupRepository::class,
            ConfiguratorSetRepository::class,
            DescriptionPullback::class,
            SpellChecker::class,
            FilterGroupRepository::class,
            MediaTool::class,
            CategoryTool::class,
            ArrayReport::class,
            ShopwareCategoryMapper::class,
            ConfiguratorSetRepository::class,
            DetailMapper::class,
            ExcelExport::class,
            ExcelImport::class,
            ExportPrices::class,
            ImageMapper::class,
            ImportClient::class,
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
    'excel'          => [
        'importFromApi' => [
            'Preise' => ImportPrices::class,
        ],
        'export' => [
            'Prices'         => [
                //'Neue Produkte' => ExportNewProducts::class,
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
