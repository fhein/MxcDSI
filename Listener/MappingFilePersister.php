<?php

namespace MxcDropshipInnocigs\Listener;


use Mxc\Shopware\Plugin\PluginListenerInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Components\Plugin\Context\UninstallContext;

class MappingFilePersister implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ UninstallContext $context)
    {
        $this->log->enter();
        $this->modelManager->getRepository(Product::class)->exportMappedProperties();
        $this->modelManager->getRepository(Variant::class)->exportMappedProperties();
        $this->log->leave();
    }
}