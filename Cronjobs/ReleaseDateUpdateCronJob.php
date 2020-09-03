<?php

namespace MxcDropshipIntegrator\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcCommons\Log\LoggerInterface;
use MxcCommons\Plugin\Service\Logger;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Throwable;
use DateTime;

class ReleaseDateUpdateCronJob implements SubscriberInterface
{
    protected $modelManager = null;
    protected $repository = null;

    /** @var Logger */
    protected $log = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipReleaseDateUpdate' => 'onReleaseDateUpdate',
        ];
    }

    public function onReleaseDateUpdate($job)
    {
        $services = MxcDropshipIntegrator::getServices();
        /** @var LoggerInterface $log */
        $this->log = $services->get('logger');
        $this->modelManager = $services->get('models');
        $this->repository = $this->modelManager->getRepository(Product::class);
        $result = true;

        $start = date('d-m-Y H:i:s');

        try {
            $this->updateReleaseDates();
        } catch (Throwable $e) {
            $this->log->except($e, false, false);
            $result = false;
        }
        $end = date('d-m-Y H:i:s');

        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $msg = 'Update release dates cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $this->log->info($msg) : $this->log->err($msg);

        return $result;
    }

    protected function updateReleaseDates()
    {
        $articles = $this->repository->getArticlesWithReleaseDate();
        $today = new DateTime();

        /** @var Article $article */
        foreach ($articles as $article) {
            $msg = null;
            $releaseDate = $this->getArticleReleaseDate($article);
            // unset release date if the article is in stock
            if ($this->isInStock($article)) {
                $releaseDate = null;
                $msg = 'Release date unset: ' . $article->getName();
            } elseif ($today >= $releaseDate) {
                $releaseDate = new DateTime('+5 days');
                $msg = sprintf("Release date promoted to %s: %s", $releaseDate->format('d.m.Y'), $article->getName());
            }
            // This will sync the product release date also and will sync the release date of all article details
            $this->setReleaseDate($article, $releaseDate);
            if ($msg) $this->log->info($msg);
        }
        $this->modelManager->flush();
    }

    // Sets both the article release date and the product release date (if available)
    protected function setReleaseDate(Article $article, ?DateTime $releaseDate)
    {
        $this->setArticleReleaseDate($article, $releaseDate);
        /** @var Product $product */
        $product = $this->repository->getProduct($article);
        if ($product !== null) {
            $product->setReleaseDate($releaseDate);
        }
    }

    protected function setArticleReleaseDate(Article $article, ?DateTime $releaseDate)
    {
        $details = $article->getDetails();
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detail->setReleaseDate($releaseDate);
        }
    }

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

    protected function isInStock(Article $article)
    {
        // @todo: We consider only the MxcDsiInnocigs adapter right now, should be replaced by triggering
        // an isInStock event over all adapters or call supplier registry

        $details = $article->getDetails();
        /** @var Detail $detail */
        foreach ($details as $detail)
        {
            $attr = ArticleTool::getDetailAttributes($detail);
            if ($detail->getInStock() > 0 ) return true;
            if ($attr['mxcbc_dsi_ic_active'] && $attr['mxcbc_dsi_ic_instock'] > 0) return true;
        }
        return false;
    }
}