<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 23.11.2018
 * Time: 20:41
 */

namespace MxcDropshipInnocigs\Plugin;

use Shopware\Components\Plugin as Base;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class Plugin extends Base
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
