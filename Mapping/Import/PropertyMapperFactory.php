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
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $modelManager = $container->get('modelManager');
        $reporter = $container->get(Reporter::class);
        $config = $this->getClassConfig($container, $requestedName);
        $config = $config->toArray();
        $flavorist = $container->get(Flavorist::class);
        $propertyDerivator = $container->get(PropertyDerivator::class);
        $log = $container->get('logger');

        $articleNameMapper = $container->get(ArticleNameMapper::class);
        $articleTypeMapper = $container->get(ArticleTypeMapper::class);
        $articleManufacturerMapper = $container->get(ArticleManufacturerMapper::class);
        $regularExpressions = $container->get(RegularExpressions::class);

        return new PropertyMapper(
            $modelManager,
            $articleNameMapper,
            $articleTypeMapper,
            $articleManufacturerMapper,
            $propertyDerivator,
            $regularExpressions,
            $flavorist,
            $reporter,
            $config,
            $log
        );
    }
}

