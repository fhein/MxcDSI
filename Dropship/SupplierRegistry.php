<?php

namespace MxcDropshipIntegrator\Dropship;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Exception\InvalidArgumentException;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

class SupplierRegistry implements ClassConfigAwareInterface, ModelManagerAwareInterface
{
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;

    const SUPPLIER_INNOCIGS = 1;
    const SUPPLIER_DEMO     = 2;

    private $services;

    public function __construct()
    {
        $this->services = MxcDropshipIntegrator::getServices();
    }

    public function getService(int $supplierId, string $service)
    {
        $className = sprintf ('%s\\%s',
            $this->classConfig[$supplierId]['namespace'],
            $service
        );
        $this->validateClass($className);
        return $this->services->get($className);
    }

    protected function validateClass(string $class) {
        if (! class_exists($class)) {
            throw new InvalidArgumentException();
        }
    }
}