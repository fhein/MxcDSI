<?php

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ExcelImportFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['excel']['importFromApi'] ?? [];

        $importers = [];
        foreach ($config as $idx => $service) {
            $importers[$idx] = $container->get($service);
        }

        return new $requestedName($importers);
    }

}