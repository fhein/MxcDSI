<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipIntegrator\Cronjobs\Innocigs;

use Enlight\Event\SubscriberInterface;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;
use MxcDropshipIntegrator\Jobs\UpdateInnocigsPrices;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Throwable;

class UpdateTrackingDataCronJob implements SubscriberInterface
{
    protected $valid = null;

    protected $log = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDsiUpdateTrackingData' => 'onUpdateTrackingData',
        ];
    }

    public function onUpdateTrackingData(/** @noinspection PhpUnusedParameterInspection */$job)
    {
        $start = date('d-m-Y H:i:s');

        $services = MxcDropshipIntegrator::getServices();
        $log = $services->get('logger');
        $result = true;

        try {

        } catch (Throwable $e) {
            $result = false;
        }

        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'TrackingData cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->error($msg);

        return $result;
    }
}