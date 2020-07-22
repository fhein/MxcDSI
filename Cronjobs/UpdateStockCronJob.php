<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use DateTime;
use Enlight\Event\SubscriberInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Models\Product;
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
            $this->syncReleaseDates();
            $this->unsetOutdatedReleaseDates();
        } catch (Throwable $e) {
            $result = false;
        }
        $end = date('d-m-Y H:i:s');

        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $msg = 'Update stock cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $this->log->info($msg) : $this->log->error($msg);

        return $result;
    }

    /** Return the release date of a Shopware article
     */
    protected function getArticleReleaseDate(Article $article)
    {
        $releaseDate = null;
        $details = $article->getDetails();
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $releaseDate = $detail->getReleaseDate();
            if ($releaseDate !== null) break;
        }
        return $releaseDate;
    }

    /**
     * Write release date to all details belonging to an article
     */
    protected function setArticleReleaseDate(Article $article, DateTime $releaseDate)
    {
        $details = $article->getDetails();
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detail->setReleaseDate($releaseDate);
        }
    }

    /** Get all products with release date set. Get associated article.
     *  Set article release date if not set already.
     *  Pullback article release date to product if article release date is set
     */
    protected function syncReleaseDates()
    {
        $products = $this->modelManager->getRepository(Product::class)->getProductsWithReleaseDate();
        /** @var Product $product */
        foreach ($products as $product) {
            /** @var Article $article */
            $article = $product->getArticle();
            if ($article === null) continue;
            $articleReleaseDate = $this->getArticleReleaseDate($article);
            if ($articleReleaseDate === null) {
                $releaseDate = DateTime::createFromFormat('d.m.Y H:i:s', $product->getReleaseDate() . ' 00:00:00');
                $this->setArticleReleaseDate($article, $releaseDate);
                $this->log->info('Setting release date of ' . $article->getName() . ' to ' . $product->getReleaseDate());
            } else {
                $releaseDate = $articleReleaseDate->format('d.m.Y');
                if ($product->getReleaseDate() != $releaseDate) {
                    $product->setReleaseDate($releaseDate);
                    $this->log->info('Pulling back release date of ' . $article->getName() . ': ' . $releaseDate);
                }
            }
        }
        $this->modelManager->flush();
    }

    // unset the (future) release date for articles in stock
    protected function unsetOutdatedReleaseDates() {
        $articles = $this->modelManager->getRepository(Article::class)->findAll();
        $productRepository = $this->modelManager->getRepository(Product::class);
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
                $this->log->info('Unsetting release dates of '. $article->getName());
                $releaseDate = null;
                $productRepository->getProduct($article)->setReleaseDate($releaseDate);
            } else {
                // if the product is still not in stock and the releasedate is in the past
                // set the release date 3 days in the future
                $now = new DateTime();
                if ($now > $releaseDate) {
                    $this->log->info('Promoting the release date of ' . $article->getName());
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