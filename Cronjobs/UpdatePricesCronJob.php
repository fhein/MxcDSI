<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropshipInnocigs\Jobs\ApplyPriceRules;
use MxcDropshipInnocigs\Jobs\UpdateInnocigsPrices;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Throwable;


class UpdatePricesCronJob implements SubscriberInterface
{
    protected $valid = null;

    protected $log = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDsiUpdatePrices' => 'onUpdatePrices',
        ];
    }

    public function onUpdatePrices(/** @noinspection PhpUnusedParameterInspection */$job)
    {
        $start = date('d-m-Y H:i:s');

        $services = MxcDropshipInnocigs::getServices();
        $log = $services->get('logger');
        $result = true;

        try {
            UpdateInnocigsPrices::run();
            ApplyPriceRules::run();
        } catch (Throwable $e) {
            $result = false;
        }
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'Update prices cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->error($msg);

        return $result;
    }
}