<?php

namespace MxcDropshipInnocigs;

use Mxc\Shopware\Plugin\Service\AugmentedObjectFactory;
use MxcDropshipInnocigs\Excel\ExcelExport;
use MxcDropshipInnocigs\Excel\ExcelImport;
use MxcDropshipInnocigs\Excel\ExcelImportFactory;
use MxcDropshipInnocigs\Excel\ExcelProductImport;
use MxcDropshipInnocigs\Excel\ExportDescription;
use MxcDropshipInnocigs\Excel\ExportDosage;
use MxcDropshipInnocigs\Excel\ExportFlavor;
use MxcDropshipInnocigs\Excel\ExportMapping;
use MxcDropshipInnocigs\Excel\ExportPrices;
use MxcDropshipInnocigs\Excel\ExportSheetFactory;
use MxcDropshipInnocigs\Excel\ImportDescription;
use MxcDropshipInnocigs\Excel\ImportDosage;
use MxcDropshipInnocigs\Excel\ImportFlavor;
use MxcDropshipInnocigs\Excel\ImportMapping;
use MxcDropshipInnocigs\Excel\ImportPrices;
use MxcDropshipInnocigs\Excel\ImportSheetFactory;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Import\ApiClientSequential;
use MxcDropshipInnocigs\Import\Credentials;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Listener\FilterTest;
use MxcDropshipInnocigs\Listener\MappingFilePersister;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Check\VariantMappingConsistency;
use MxcDropshipInnocigs\Mapping\EntityValidator;
use MxcDropshipInnocigs\Mapping\Import\AssociatedProductsMapper;
use MxcDropshipInnocigs\Mapping\Import\CapacityMapper;
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\ClassConfigFactory;
use MxcDropshipInnocigs\Mapping\Import\CommonNameMapper;
use MxcDropshipInnocigs\Mapping\Import\DescriptionMapper;
use MxcDropshipInnocigs\Mapping\Import\DosageMapper;
use MxcDropshipInnocigs\Mapping\Import\FlavorMapper;
use MxcDropshipInnocigs\Mapping\Import\ImportPiecesPerPackMapper;
use MxcDropshipInnocigs\Mapping\Import\ManufacturerMapper;
use MxcDropshipInnocigs\Mapping\Import\MappingConfigFactory;
use MxcDropshipInnocigs\Mapping\Import\NameMapper;
use MxcDropshipInnocigs\Mapping\Import\ProductMappings;
use MxcDropshipInnocigs\Mapping\Import\ProductNumberMapper;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\Import\TypeMapper;
use MxcDropshipInnocigs\Mapping\Import\VariantNumberMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ImportPriceMapper;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Mapping\Pullback\DescriptionPullback;
use MxcDropshipInnocigs\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\CategoryMapper as ArticleCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DropshippersCompanion;
use MxcDropshipInnocigs\Mapping\Shopware\ImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\OptionMapper;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
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
use MxcDropshipInnocigs\Toolbox\Shopware\MediaTool;

return [
    'plugin'   => [
        MappingFilePersister::class,
    ],
    'doctrine' => [
        'models'     => [
            Group::class,
            Model::class,
            Option::class,
            Product::class,
            Variant::class,
        ],
        'attributes' => [
            's_articles_attributes' => [
                'mxc_dsi_type'         => ['type' => 'string'],
                'mxc_dsi_subtype'      => ['type' => 'string'],
                'mxc_dsi_common_name'  => ['type' => 'string'],
                'mxc_dsi_manufacturer' => ['type' => 'string'],
                'mxc_dsi_supplier'     => ['type' => 'string'],
                'mxc_dsi_brand'        => ['type' => 'string'],

                'mxc_dsi_flavor'         => ['type' => 'string'],
                'mxc_dsi_flavor_group'   => ['type' => 'string'],
                'mxc_dsi_base'           => ['type' => 'string'],
                'mxc_dsi_nicotine'       => ['type' => 'string'],
                'mxc_dsi_bottle_size'    => ['type' => 'string'],
                'mxc_dsi_bottle_content' => ['type' => 'string'],

                'mxc_dsi_mod_cell_type'        => ['type' => 'string'],
                'mxc_dsi_mod_power'            => ['type' => 'string'],
                'mxc_dsi_mod_capacity'         => ['type' => 'string'],
                'mxc_dsi_mod_materials'        => ['type' => 'string'],
                'mxc_dsi_mod_charging_current' => ['type' => 'string'],
                'mxc_dsi_mod_processor'        => ['type' => 'string'],
                'mxc_dsi_mod_output_modes'     => ['type' => 'string'],
                'mxc_dsi_mod_output_voltage'   => ['type' => 'string'],
                'mxc_dsi_mod_temperatur_range' => ['type' => 'string'],
                'mxc_dsi_mod_resistance_range' => ['type' => 'string'],
                'mxc_dsi_mod_thread_type'      => ['type' => 'string'],
                'mxc_dsi_mod_special_features' => ['type' => 'string'],
                'mxc_dsi_mod_size'             => ['type' => 'string'],

                'mxc_dsi_clr_tank_volume'  => ['type' => 'string'],
                'mxc_dsi_clr_diameter'     => ['type' => 'string'],
                'mxc_dsi_clr_thread_type'  => ['type' => 'string'],
                'mxc_dsi_clr_driptip_type' => ['type' => 'string'],
                'mxc_dsi_clr_materials'    => ['type' => 'string'],
                'mxc_dsi_clr_airflow'      => ['type' => 'string'],
                'mxc_dsi_clr_filling'      => ['type' => 'string'],
                'mxc_dsi_clr_inhalation'   => ['type' => 'string'],

                'mxc_dsi_master' => ['type' => 'string'],
            ],
        ],
    ],

    'services'     => [
        'factories' => [
            ProductMappings::class           => AugmentedObjectFactory::class,
            ImportPiecesPerPackMapper::class => AugmentedObjectFactory::class,
            ProductNumberMapper::class       => AugmentedObjectFactory::class,
            TypeMapper::class                => AugmentedObjectFactory::class,
            VariantNumberMapper::class       => AugmentedObjectFactory::class,
            AssociatedProductsMapper::class  => AugmentedObjectFactory::class,
            AssociatedArticlesMapper::class  => AugmentedObjectFactory::class,
            VariantMappingConsistency::class => AugmentedObjectFactory::class,
            ImportPriceMapper::class         => AugmentedObjectFactory::class,

            ArticleTool::class                 => AugmentedObjectFactory::class,
            ConfiguratorGroupRepository::class => AugmentedObjectFactory::class,
            ConfiguratorSetRepository::class   => AugmentedObjectFactory::class,
            DescriptionPullback::class         => AugmentedObjectFactory::class,
            FilterGroupRepository::class       => AugmentedObjectFactory::class,
            MappingFilePersister::class        => AugmentedObjectFactory::class,
            MediaTool::class                   => AugmentedObjectFactory::class,

            CategoryTool::class => AugmentedObjectFactory::class,

            CommonNameMapper::class  => MappingConfigFactory::class,
            DosageMapper::class      => MappingConfigFactory::class,
            CapacityMapper::class    => MappingConfigFactory::class,
            FlavorMapper::class      => MappingConfigFactory::class,
            NameMapper::class        => MappingConfigFactory::class,
            CategoryMapper::class    => MappingConfigFactory::class,
            DescriptionMapper::class => MappingConfigFactory::class,

            ImportDosage::class      => AugmentedObjectFactory::class,
            ImportFlavor::class      => AugmentedObjectFactory::class,
            ImportDescription::class => AugmentedObjectFactory::class,
            ImportMapping::class     => AugmentedObjectFactory::class,

            ExportDosage::class      => AugmentedObjectFactory::class,
            ExportFlavor::class      => AugmentedObjectFactory::class,
            ExportDescription::class => AugmentedObjectFactory::class,
            ExportMapping::class     => AugmentedObjectFactory::class,

            ExcelProductImport::class => ExcelImportFactory::class,
        ],
        'magicals'  => [
            ApiClient::class,
            ApiClientSequential::class,
            ArrayReport::class,
            ArticleCategoryMapper::class,
            ConfiguratorSetRepository::class,
            Credentials::class,
            DetailMapper::class,
            DropshippersCompanion::class,
            ExcelExport::class,
            ExcelImport::class,
            ExportPrices::class,
            FilterTest::class,
            ImageMapper::class,
            ImportClient::class,
            ImportMapper::class,
            ImportPrices::class,
            ManufacturerMapper::class,
            NameMappingConsistency::class,
            OptionMapper::class,
            PriceMapper::class,
            ProductMapper::class,
            PropertyMapper::class,
            RegexChecker::class,
            RegularExpressions::class,
        ],
    ],
    'class_config' => [
        AssociatedProductsMapper::class => 'AssociatedProductsMapper.config.php',
        CategoryMapper::class           => 'CategoryMapper.config.php',
        CommonNameMapper::class         => 'CommonNameMapper.config.php',
        FlavorMapper::class             => 'FlavorMapper.config.php',
        ImportClient::class             => 'ImportClient.config.php',
        ImportMapper::class             => 'ImportMapper.config.php',
        ManufacturerMapper::class       => 'ManufacturerMapper.config.php',
        NameMapper::class               => 'NameMapper.config.php',
        ProductMappings::class          => 'ProductMappings.config.php',
        ProductNumberMapper::class      => 'ProductNumberMapper.config.php',
        PropertyMapper::class           => 'PropertyMapper.config.php',
        TypeMapper::class               => 'TypeMapper.config.php',
        VariantNumberMapper::class      => 'VariantNumberMapper.config.php',

        ProductMapper::class => [
            'root_category' => 'Deutsch',
        ],
    ],
    'excel'        => [
        'import' => [
            'Preise'       => ImportPrices::class,
            'Dosierung'    => ImportDosage::class,
            'Geschmack'    => ImportFlavor::class,
            'Beschreibung' => ImportDescription::class,
            'Mapping'      => ImportMapping::class,
        ],
        'export' => [
            'Preise'          => ExportPrices::class,
            'Dosierung'       => ExportDosage::class,
            'Geschmack'       => ExportFlavor::class,
            'Beschreibung'    => ExportDescription::class,
            'Mapping'         => ExportMapping::class,
        ],
    ],
];
