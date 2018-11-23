<?php

namespace MxcDropshipInnocigs;

require __DIR__ . '/vendor/autoload.php';

use MxcDropshipInnocigs\Plugin\Application;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class MxcDropshipInnocigs extends Plugin
{
    public function install(InstallContext $context)
    {
        Application::fromPlugin($this)->trigger(__FUNCTION__, ['context' => $context]);
    }

    public function uninstall(UninstallContext $context)
    {
        Application::fromPlugin($this)->trigger(__FUNCTION__, ['context' => $context]);
    }

    public function activate(ActivateContext $context)
    {
        Application::fromPlugin($this)->trigger(__FUNCTION__, ['context' => $context]);
    }

    public function deactivate(DeactivateContext $context)
    {
        Application::fromPlugin($this)->trigger(__FUNCTION__, ['context' => $context]);
    }
}
