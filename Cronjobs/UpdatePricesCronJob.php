<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use Enlight\Event\SubscriberInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

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
        $services = MxcDropshipInnocigs::getServices();
        /** @var LoggerInterface $log */
        $this->log = $services->get('logger');
        $this->modelManager = Shopware()->Models();
        $result = true;

        return $result;
    }
}