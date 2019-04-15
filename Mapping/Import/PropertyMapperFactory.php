<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use Zend\ServiceManager\Factory\FactoryInterface;

class PropertyMapperFactory implements FactoryInterface
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
        $productMappers = [
            // no requirements, sets Shopware number
            'code'          => $container->get(ProductNumberMapper::class),
            // requires product's manufacturer, sets brand and supplier
            'manufacturer'  => $container->get(ManufacturerMapper::class),
            // requires brand, sets name
            'name'          => $container->get(NameMapper::class),
            // requires name, sets piecesPerPack
            'piecesPerPack' => $container->get(ImportPiecesPerPackMapper::class),
            // requires name, sets commonName
            'commonName'    => $container->get(CommonNameMapper::class),
            // requires name, sets type
            'type'          => $container->get(TypeMapper::class),
            // requires type, sets dosage
            'dosage'        => $container->get(DosageMapper::class),
            // requires supplier, brand and name, sets category
            'category'      => $container->get(CategoryMapper::class),
            // requires manual config, sets flavor
            'flavor'        => $container->get(FlavorMapper::class),
        ];

        $variantMappers = [
            // no requirements, sets Shopware number
            'code' => $container->get(VariantNumberMapper::class),
        ];

        $associatedProductsMapper = $container->get(AssociatedProductsMapper::class);
        $mappings = $container->get(ImportMappings::class);

        $regularExpressions = $container->get(RegularExpressions::class);

        return new PropertyMapper(
            $modelManager,
            $mappings,
            $associatedProductsMapper,
            $regularExpressions,
            $flavorist,
            $reporter,
            $productMappers,
            $variantMappers,
            $config,
            $log
        );
    }
}

