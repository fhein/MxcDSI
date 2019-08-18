<?php

namespace MxcDropshipInnocigs\Import;

use Enlight\Event\SubscriberInterface;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

class ImportCronJob implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDsiImport' => 'onImportCronJob'
        ];
    }

    public function onImportCronJob(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        MxcDropshipInnocigs::getServices()->get(ImportClient::class)->import(true);

        return true;
    }
}