<?php

namespace MxcDropshipInnocigs;

require __DIR__ . '/vendor/autoload.php';

use Mxc\Shopware\Plugin\Plugin;
use Mxc\Shopware\Plugin\Service\ServicesFactory;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\UninstallContext;

const MXC_DELIMITER_L1 = '#!#';
const MXC_DELIMITER_L2 = '##!##';

class MxcDropshipInnocigs extends Plugin {

    protected $activateClearCache = ActivateContext::CACHE_LIST_ALL;
    protected $uninstallClearCache = UninstallContext::CACHE_LIST_ALL;

    private static $services;

    public static function getServices()
    {
        if (self::$services !== null) return self::$services;
        $factory = new ServicesFactory();
        self::$services = $factory->getServices(__DIR__);
        return self::$services;

    }
}

