<?php

namespace MxcDropshipIntegrator\Mapping\Check;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcDropshipIntegrator\Mapping\Import\CategoryMapper;
use MxcDropshipIntegrator\Mapping\Import\NameMapper;
use MxcDropshipIntegrator\Mapping\Import\PropertyMapper;
use MxcDropshipIntegrator\Mapping\Import\TypeMapper;
use MxcCommons\Toolbox\Regex\RegexChecker;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use ReflectionClass;

class RegularExpressionsFactory implements FactoryInterface
{
    protected $configPath;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->configPath = $container->get('config')['plugin_config_path'];
        $config = [];

        $nameMapperConfig = $this->getClassConfig(NameMapper::class);
        foreach (['name_prepare', 'name_cleanup', 'product_name_replacements', 'product_names'] as $key) {
            $config[$key] = @$nameMapperConfig[$key];
        }

        $typeMapperConfig = $this->getClassConfig(TypeMapper::class);
        $key = 'name_type_mapping';
        $config[$key] = @$typeMapperConfig[$key];

        $regexChecker = $container->get(RegexChecker::class);
        $log = $container->get('logger');

        return new RegularExpressions($regexChecker, $config, $log);
    }

    protected function getClassConfig(string $class)
    {
        // interesting: Reflexion class is faster than string operations in retrieving the class name w/o namespace
        $configFile = (new ReflectionClass($class))->getShortName() . '.config.php';
        $filename = $this->configPath . '/' . $configFile;
        // we support the phpx extesnion also, temporarily to exclude some config files form code inspection
        if (! file_exists($filename)) {
            $filename .= 'x';
            if (! file_exists($filename)) {
                return [];
            }
        }

        $classConfig = include $filename;
        if (! is_array($classConfig)) {
            return [];
        }
        return $classConfig;
    }
}

