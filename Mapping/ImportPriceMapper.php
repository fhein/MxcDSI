<?php /** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping;

use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\TaxTool;

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

            $vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;

            $newUvp = StringTool::tofloat($model->getRecommendedRetailPrice());
            $newUvp /= $vatFactor;

            $currentUvp = $variant->getRecommendedRetailPrice();

            if (round($newUvp,2) !== round($currentUvp, 2)) {
                $variant->setRecommendedRetailPriceOld($currentUvp);
                $variant->setRecommendedRetailPrice($newUvp);
                $this->log->info(sprintf("UVP change: Variant %s (old: %s, new: %s)",
                    $icNumber,
                    round($currentUvp * $vatFactor, 2),
                    round($newUvp * $vatFactor, 2)
                ));
            }

            $innocigsPurchasePrice = StringTool::tofloat($model->getPurchasePrice());
            $currentPurchasePrice = $variant->getPurchasePrice();

            if (round($innocigsPurchasePrice, 2) !== round($currentPurchasePrice, 2)) {
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