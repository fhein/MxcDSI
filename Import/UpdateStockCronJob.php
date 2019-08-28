<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Import;

use Enlight\Event\SubscriberInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Article\Detail;
use Shopware\Models\Plugin\Plugin;

class UpdateStockCronJob implements SubscriberInterface
{
    protected $valid = null;

    protected $log = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDsiUpdateStock' => 'onUpdateStockCronJob',
        ];
    }

    public function onUpdateStockCronJob(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        $services = MxcDropshipInnocigs::getServices();
        /** @var LoggerInterface $log */
        $this->log = $services->get('logger');
        $this->modelManager = Shopware()->Models();
        $result = true;

        if (! $this->validateCompanion()) {
            $this->log->warn('Update stock cronjob: Companion is not installed. Nothing done');
            return false;
        }

        $start = date('d-m-Y H:i:s');

        try {
            $this->updateStockInfo();
        } catch (Throwable $e) {
            $result = false;
        }
        $end = date('d-m-Y H:i:s');
        $resultMsg = $result === true ? '. Succcess.' : '. Failure.';
        $this->log->debug('Update stock cronjob ran from ' . $start . ' to ' . $end . $resultMsg);

        return $result;
    }

    protected function updateStockInfo()
    {
        $apiClient = MxcDropshipInnocigs::getServices()->get(ApiClient::class);
        $details = $this->modelManager->getRepository(Detail::class)->findAll();
        $info = $apiClient->getItemList(true);
        $stockInfo = $apiClient->getAllStockInfo();
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $attr = ArticleTool::getDetailAttributes($detail);
            $active = $attr['dc_ic_active'];
            $icNumber = $attr['dc_ic_ordernumber'];
            if ($active === 0 || $active === null || $icNumber === "" || $icNumber === null) continue;
            if ($info[$icNumber] !== null) {
                // record from InnoCigs available
                $purchasePrice = $info[$icNumber]['purchasePrice'];
                ArticleTool::setDetailAttribute($detail, 'dc_ic_purchasing_price', $purchasePrice);
                ArticleTool::setDetailAttribute($detail, 'dc_ic_retail_price', $info[$icNumber]['recommendedRetailPrice']);
                ArticleTool::setDetailAttribute($detail, 'dc_ic_instock', intval($stockInfo[$icNumber] ?? 0));
                $purchasePrice = floatval(str_replace(',', '.', $purchasePrice));
                $detail->setPurchasePrice($purchasePrice);
            } else {
                // no record from InnoCigs available
                ArticleTool::setDetailAttribute($detail, 'dc_ic_instock', 0);
                ArticleTool::setDetailAttribute($detail, 'dc_ic_purchasing_price', 0);
            }
        }
        $this->modelManager->flush();
    }

    protected function validateCompanion() {
        if (! is_bool($this->valid)) {
            if (null === $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs'])) {
                $this->valid = false;
            } else {
                $this->valid = true;
            }
        };
        return $this->valid;
    }
}