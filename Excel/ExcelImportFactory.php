<?php

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\ObjectAugmentationTrait;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ExcelImportFactory implements FactoryInterface
{
    use ObjectAugmentationTrait;

    /** @noinspection PhpUnused */
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
        $config = $container->get('config')['excel']['import'] ?? [];

        $importers = [];
        foreach ($config as $idx => $service) {
            $importers[$idx] = $container->get($service);
        }

        return $this->augment($container, new $requestedName($importers));
    }

}