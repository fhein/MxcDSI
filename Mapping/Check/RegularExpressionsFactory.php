<?php

namespace MxcDropshipInnocigs\Mapping\Check;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\NameMapper;
use MxcDropshipInnocigs\Mapping\Import\TypeMapper;
use MxcDropshipInnocigs\Toolbox\Regex\RegexChecker;
use Zend\ServiceManager\Factory\FactoryInterface;

class RegularExpressionsFactory implements FactoryInterface
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
        $articleNameMapperConfig = $this->getClassConfig($container, NameMapper::class);
        foreach (['name_prepare', 'name_cleanup', 'product_name_replacements', 'product_names'] as $key) {
            $config[$key] = $articleNameMapperConfig[$key];
        }

        $articleTypeMapperConfig = $this->getClassConfig($container, TypeMapper::class);
        $key = 'name_type_mapping';
        $config[$key] = $articleTypeMapperConfig[$key];

        $propertyMapperConfig = $this->getClassConfig($container, CategoryMapper::class);
        $key = 'categories';
        $config[$key] = $propertyMapperConfig[$key];

        $regexChecker = $container->get(RegexChecker::class);
        $log = $container->get('logger');

        return new RegularExpressions($regexChecker, $config, $log);
    }
}

