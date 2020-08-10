<?php

namespace MxcDropshipIntegrator\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

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
        MxcDropshipIntegrator::getServices()->get(ImportClient::class)->import(true);

        return true;
    }
}