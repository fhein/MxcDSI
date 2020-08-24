<?php

namespace MxcDropshipIntegrator\Dropship;

use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareInterface;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Exception\InvalidArgumentException;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class DropshipManager implements ClassConfigAwareInterface, ModelManagerAwareInterface, DatabaseAwareInterface
{
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;
    use DatabaseAwareTrait;

    protected $auto = true;
    protected $delivery;

    const NO_ERROR          = 0;

    const STATUS_NEW        = 0;
    const STATUS_OK         = 1;
    const STATUS_ERROR      = 2;

    // delivery modes
    const DELIVERY_OWNSTOCK_ONLY        = 0;
    const DELIVERY_PREFER_OWNSTOCK      = 1;
    const DELIVERY_PREFER_DROPSHIP      = 2;
    const DELIVERY_DROPSHIP_ONLY        = 3;

    // constants for all available modules
    const SUPPLIER_SELF     = 0;
    const SUPPLIER_INNOCIGS = 1;
    const SUPPLIER_DEMO     = 2;

    protected $modules = [];

    public function init()
    {
        $moduleConfigs = @$this->classConfig['modules'] ?? [];
        $modules = [];
        foreach ($moduleConfigs as $supplierId => $module) {
            $v = @$module['namespace'];
            if ($v === null || !is_string($v)) {
                continue;
            }
            $v = @$module['name'];
            if ($v === null || !is_string($v)) {
                continue;
            }

            // do not register adapters which are not present and active
            $plugin = @$module['plugin'];
            if ($plugin === null || !is_string($plugin)) {
                continue;
            }
            if (!$this->db->fetchOne('SELECT active FROM s_core_plugins WHERE name = ?', [$plugin])) {
                continue;
            }

            $class = $plugin . '\\' . $plugin;
            if (!class_exists($class)) {
                continue;
            }
            if (!method_exists($class, 'getServices')) {
                continue;
            }

            // we eagerly load the services management of all active modules because we need them anyway
            $module['service_manager'] = $services = call_user_func($class . '::getServices');

            // services cache
            $module['services'] = [];

            // additional checks could be applied here later

            // at this point we have a properly configured active dropship adapter module
            $this->modules[$supplierId] = $module;
        }

        $config = Shopware()->Config();
        $this->auto = $config->get('mxcbc_dsi_auto');
        $this->delivery = $config->get('mxcbc_dsi_delivery');

    }

    public function getService(int $supplierId, string $service)
    {
        $module = $this->modules[$supplierId];
        if ($module === null) return null;
        $className = sprintf('%s\\%s', $module['namespace'], $service);
        $service = $module['services'][$className] ?? $module['service_manager']->get($className);
        $this->modules[$supplierId][$className] = $service;
        return $service;
    }

    public function getStockInfo($sArticle)
    {
        // ask each dropship adapter about the # of items in stock
        $stockData = [];
        foreach ($this->modules as $supplierId => $module) {
            $stockInfo = $this->getService($supplierId, 'StockInfo')->getStockInfo($sArticle);

            if (empty($stockInfo)) {
                continue;
            }

            $stockData[] = $stockInfo;
        }
        return $stockData;
    }

    public function getSupplierAndStock(array $sArticle)
    {

    }

    public function isAuto() {
        return $this->auto;
    }

    public function processOrder(array $order)
    {
        $details = $order['details'];
        $supplierIds = array_unique(array_column($details, 'mxcbc_dsi_suppliers'));
        foreach ($supplierIds as $supplierId) {
            $processor = $this->getService($supplierId, 'OrderProcessor');
            $processor->processOrder($order);
        }
    }
}