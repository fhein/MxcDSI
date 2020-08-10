<?php

namespace MxcDropshipIntegrator\Listener;


use MxcCommons\Plugin\PluginListenerInterface;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
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