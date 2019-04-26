<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use Zend\ServiceManager\Factory\FactoryInterface;

class PropertyMapperFactory implements FactoryInterface
{
    use ObjectAugmentationTrait;

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
        $flavorist = $container->get(Flavorist::class);

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
            // requires manual config, sets retailPriceDampfplanet and retailPriceOthers
            'competitor'    => $container->get(CompetitorPricesMapper::class),
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

        return $this->augment($container, new PropertyMapper(
            $mappings,
            $associatedProductsMapper,
            $regularExpressions,
            $flavorist,
            $productMappers,
            $variantMappers
        ));
    }
}

