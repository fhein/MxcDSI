<?php

namespace MxcDropshipIntegrator\Listener;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use Shopware\Components\Plugin\Context\UninstallContext;

class MappingFilePersister implements AugmentedObject
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