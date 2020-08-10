<?php

namespace MxcDropshipIntegrator\Mapping\Check;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ClassConfigTrait;
use MxcDropshipIntegrator\Mapping\Import\CategoryMapper;
use MxcDropshipIntegrator\Mapping\Import\NameMapper;
use MxcDropshipIntegrator\Mapping\Import\TypeMapper;
use MxcDropshipIntegrator\Toolbox\Regex\RegexChecker;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

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
            $config[$key] = @$articleNameMapperConfig[$key];
        }

        $articleTypeMapperConfig = $this->getClassConfig($container, TypeMapper::class);
        $key = 'name_type_mapping';
        $config[$key] = @$articleTypeMapperConfig[$key];

        $propertyMapperConfig = $this->getClassConfig($container, CategoryMapper::class);
        $key = 'categories';
        $config[$key] = @$propertyMapperConfig[$key];

        $regexChecker = $container->get(RegexChecker::class);
        $log = $container->get('logger');

        return new RegularExpressions($regexChecker, $config, $log);
    }
}

