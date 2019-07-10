<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

class ImportPriceMapper implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    public function import(array $changes)
    {
        // @todo: Continue here ...
        $variants = $this->modelManager->getRepository(Variant::class)->getAllIndexed();
        $models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
        /** @var Model $model */
        foreach ($models as $model) {
            /** @var Variant $variant */
            $variant = $variants[$model->getModel()] ?? null;

            if (! $variant) continue;

            $icNumber = $variant->getIcNumber();

            $innocigsRecommendedRetailPrice = str_replace(',', '.', $model->getRecommendedRetailPrice());
            $currentRecommendedRetailPrice = $variant->getRecommendedRetailPrice();
            
            if ($innocigsRecommendedRetailPrice !== $currentRecommendedRetailPrice) {
                $variant->setRecommendedRetailPriceOld($currentRecommendedRetailPrice);
                $variant->setRecommendedRetailPrice($innocigsRecommendedRetailPrice);
                $this->log->info(sprintf("UVP change: Variant %s (old: %s, new: %s)",
                    $icNumber,
                    $currentRecommendedRetailPrice,
                    $innocigsRecommendedRetailPrice));
            }

            $innocigsPurchasePrice = str_replace(',', '.', $model->getPurchasePrice());
            $currentPurchasePrice = $variant->getPurchasePrice();

            if ($innocigsPurchasePrice !== $currentPurchasePrice) {
                $variant->setPurchasePriceOld($currentPurchasePrice);
                $variant->setPurchasePrice($innocigsPurchasePrice);
                $this->log->info(sprintf("EK change: Variant %s (old: %s, new: %s)",
                    $icNumber,
                    $currentPurchasePrice,
                    $innocigsPurchasePrice));
            }
        }
        $this->modelManager->flush();
    }
}