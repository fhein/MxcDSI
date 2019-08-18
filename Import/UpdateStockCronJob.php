<?php /** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Import;

use Enlight\Event\SubscriberInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
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

        if (! $this->validateCompanion()) return $result;

        $this->log->debug('Update stock cronjob. Start: ' . date('d-m-Y H:i:s'));

        try {
            $this->updateStockInfo();
        } catch (Throwable $e) {
            $result = false;
        }

        $this->log->debug('Update stock cronjob. End: ' . date('d-m-Y H:i:s'));
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
            $attribute = $detail->getAttribute();
            if ($attribute === null) continue; // @todo: Error handling
            $active = $attribute->getDcIcActive();
            $icNumber = $attribute->getDcIcOrdernumber();
            if ($active === 0 || $active === null || $icNumber === "" || $icNumber === null) continue;
            if ($info[$icNumber] !== null) {
                // record from InnoCigs available
                $attribute->setDcIcInstock(intval($stockInfo[$icNumber]));
                $purchasePrice = $info[$icNumber]['purchasePrice'];
                $attribute->setDcIcPurchasingPrice($purchasePrice);
                $attribute->setDcIcArticlename($info[$icNumber]['name']);
                $purchasePrice = floatval(str_replace(',', '.', $purchasePrice));
                $detail->setPurchasePrice($purchasePrice);
                $attribute->setDcIcRetailPrice($info[$icNumber]['recommendedRetailPrice']);
            } else {
                // no record from InnoCigs available
                $attribute->setDcIcInstock(0);
                $attribute->setDcIcPurchasingPrice(0);
            }
        }
        $this->modelManager->flush();
    }

    protected function validateCompanion() {
        if (! is_bool($this->valid)) {
            $className = 'Shopware\Models\Attribute\Article';
            if (null === $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs'])
                || !(method_exists($className, 'setDcIcOrderNumber')
                    && method_exists($className, 'setDcIcArticleName')
                    && method_exists($className, 'setDcIcPurchasingPrice')
                    && method_exists($className, 'setDcIcRetailPrice')
                    && method_exists($className, 'setDcIcActive')
                    && method_exists($className, 'setDcIcInstock'))
            ) {
                $this->log->warn('Can not prepare articles for dropship orders. Dropshipper\'s Companion is not installed.');
                $this->valid = false;
            } else {
                $this->valid = true;
            }
        };
        return $this->valid;
    }
}