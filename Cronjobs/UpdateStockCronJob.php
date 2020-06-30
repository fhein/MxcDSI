<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use DateTime;
use Enlight\Event\SubscriberInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Article\Article;
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
            $this->unsetOutdatedReleaseDates();
        } catch (Throwable $e) {
            $result = false;
        }
        $end = date('d-m-Y H:i:s');
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $this->log->debug('Update stock cronjob ran from ' . $start . ' to ' . $end . $resultMsg);

        return $result;
    }

    // unset the (future) release date for articles in stock
    protected function unsetOutdatedReleaseDates() {
        $articles = $this->modelManager->getRepository(Article::class)->findAll();
        /** @var Article $article */
        foreach ($articles as $article) {
            $details = $article->getDetails();
            /** @var Detail $detail */

            // check if any of the details has an release date !== null
            $releaseDate = null;
            foreach ($details as $detail) {
                $releaseDate = $detail->getReleaseDate();
                if ($releaseDate !== null) break;
            }
            // skip article if there is no release date
            if ($releaseDate === null) continue;

            // determine if a quantity of any of the details is in stock
            $instock = 0;
            foreach ($details as $detail) {
                $attr = ArticleTool::getDetailAttributes($detail);
                $instock += $attr['dc_ic_instock'];
            }
            // unset the release date if the any of the product's variants is in stock
            if ($instock !== 0) {
                $this->log->debug('Unsetting release dates of '. $article->getName());
                $releaseDate = null;
            } else {
                // if the product is still not in stock and the releasedate is in the past
                // set the release date 3 days in the future
                $now = new DateTime();
                if ($now > $releaseDate) {
                    $this->log->debug('Promoting the release date of ' . $article->getName());
                    $releaseDate = new DateTime('+3 days');
                }
            }

            // sync release dates if the article is still out of stock
            // unset release date if at least one variant is in stock
            foreach ($details as $detail) {
                $detail->setReleaseDate($releaseDate);
            }
        }
        $this->modelManager->flush();
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
            $icNumber = $attr['dc_ic_ordernumber'];
            if (empty($icNumber)) continue;

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