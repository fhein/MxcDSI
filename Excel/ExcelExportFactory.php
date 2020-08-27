<?php /** @noinspection PhpUnused */

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ExcelExportFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['excel']['export'] ?? [];
        $config = $config[$options[0]];

        $exporters = [];
        foreach ($config as $idx => $service) {
            $exporters[$idx] = $container->get($service);
        }

        return new ExcelExport($exporters);
    }
}