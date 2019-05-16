<?php

namespace MxcDropshipInnocigs\Import;

use Enlight\Event\SubscriberInterface;
use Mxc\Shopware\Plugin\Service\ServicesTrait;

class ImportCronJob implements SubscriberInterface
{
    use ServicesTrait;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDsiImport' => 'onImportCronJob'
        ];
    }

    public function onImportCronJob(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        $this->getServices();
        $this->services->get(ImportClient::class)->import();

        return true;
    }
}