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
        $config = $container->get('config')['excel']['export'] ?? [];

        $exporters = [];
        foreach ($config as $idx => $service) {
            $exporters[$idx] = $container->get($service);
        }

        return new ExcelExport($exporters);
    }
}