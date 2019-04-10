<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportPropertyMapperFactory implements FactoryInterface
{
    use ClassConfigTrait;

    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $modelManager = $container->get('modelManager');
        $reporter = $container->get(Reporter::class);
        $config = $this->getClassConfig($container, $requestedName);
        $flavorist = $container->get(Flavorist::class);
        $log = $container->get('logger');

        // take care of the mapper dependencies
        $articleMappers = [
            // no requirements, sets Shopware number
            'code'          => $container->get(ImportArticleCodeBaseImportMapper::class),
            // requires article's manufacturer, sets brand and supplier
            'manufacturer'  => $container->get(ImportManufacturerMapper::class),
            // requires brand, sets name
            'name'          => $container->get(ImportNameMapper::class),
            // requires name, sets piecesPerPack
            'piecesPerPack' => $container->get(ImportPiecesPerPackMapper::class),
            // requires name, sets commonName
            'commonName'    => $container->get(ImportCommonNameMapper::class),
            // requires name, sets type
            'type'          => $container->get(ImportTypeMapper::class),
            // requires type, sets dosage
            'dosage'        => $container->get(ImportDosageMapper::class),
            // requires supplier, brand and name, sets category
            'category'      => $container->get(ImportCategoryMapper::class),
            // requires manual config, sets flavor
            'flavor'        => $container->get(ImportFlavorMapper::class),
        ];

        $variantMappers = [
            // no requirements, sets Shopware number
            'code' => $container->get(ImportVariantCodeBaseImportMapper::class),
        ];

        $associatedArticlesMapper = $container->get(ImportAssociatedArticlesMapper::class);
        $mappings = $container->get(ImportMappings::class);

        $regularExpressions = $container->get(RegularExpressions::class);

        return new ImportPropertyMapper(
            $modelManager,
            $mappings,
            $associatedArticlesMapper,
            $regularExpressions,
            $flavorist,
            $reporter,
            $articleMappers,
            $variantMappers,
            $config,
            $log
        );
    }
}

