<?php

namespace MxcDropshipInnocigs\Excel;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ExcelExportFactory implements FactoryInterface
{
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
        $log = $container->get('logger');
        $config = $container->get('config')['excel']['export'] ?? [];

        $sheets = [];
        foreach ($config as $idx => $service) {
            $sheets[$idx] = $container->get($service);
        }

        return new ExcelExport($modelManager, $sheets, $log);
    }
}