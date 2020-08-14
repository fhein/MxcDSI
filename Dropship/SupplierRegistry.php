<?php

namespace MxcDropshipIntegrator\Dropship;

use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Exception\InvalidArgumentException;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class SupplierRegistry implements ClassConfigAwareInterface, ModelManagerAwareInterface
{
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;

    const SUPPLIER_INNOCIGS = 1;
    const SUPPLIER_DEMO     = 2;

    private $services;

    public function __construct()
    {
    }

    public function getService(int $supplierId, string $service)
    {
        $services = $this->getServices($supplierId);
        if ($services === null) return null;

        $config = @$this->classConfig[$supplierId];
        $className = sprintf ('%s\\%s',
            $config['namespace'],
            $service
        );
        return $services->get($className);
    }

    protected function getServices (int $supplierId)
    {
        if (@$this->services[$supplierId] !== null) return $this->services[$supplierId];

        $module = @$this->classConfig[$supplierId]['module'];
        if ($module === null || ! class_exists($module)) return null;
        $services = call_user_func($module.'::getServices');
        $this->services[$supplierId] = $services;
        return $services;
    }
}