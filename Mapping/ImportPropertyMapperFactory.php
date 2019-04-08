<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
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
use MxcDropshipInnocigs\Mapping\Import\VariantCodeMapper;
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
        $config = $config->toArray();
        $flavorist = $container->get(Flavorist::class);
        $log = $container->get('logger');

        // take care of the mapper dependencies
        $articleMappers = [
            // no requirements, sets Shopware number
            'code'          => $container->get(ArticleCodeMapper::class),
            // requires article's manufacturer, sets brand and supplier
            'manufacturer'  => $container->get(ArticleManufacturerMapper::class),
            // requires brand, sets name
            'name'          => $container->get(ArticleNameMapper::class),
            // requires name, sets piecesPerPack
            'piecesPerPack' => $container->get(ArticlePiecesPerPackMapper::class),
            // requires name, sets commonName
            'commonName'    => $container->get(ArticleCommonNameMapper::class),
            // requires name, sets type
            'type'          => $container->get(ArticleTypeMapper::class),
            // requires type, sets dosage
            'dosage'        => $container->get(AromaDosageMapper::class),
            // requires supplier, brand and name, sets category
            'category'      => $container->get(ArticleCategoryMapper::class),
            // requires manual config, sets flavor
            'flavor'        => $container->get(ArticleFlavorMapper::class),
        ];

        $variantMappers = [
            // no requirements, sets Shopware number
            'code' => $container->get(VariantCodeMapper::class),
        ];

        $associatedArticlesMapper = $container->get(AssociatedArticlesMapper::class);

        $regularExpressions = $container->get(RegularExpressions::class);

        return new ImportPropertyMapper(
            $modelManager,
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

