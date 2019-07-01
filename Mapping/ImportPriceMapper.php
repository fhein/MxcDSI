<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

class ImportPriceMapper implements ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

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
            $variant->setRecommendedRetailPriceOld($variant->getRecommendedRetailPrice());
            $variant->setRecommendedRetailPrice($model->getRecommendedRetailPrice());
            $variant->setPurchasePriceOld($variant->getPurchasePrice());
            $variant->setPurchasePrice($model->getPurchasePrice());
        }
        $this->modelManager->flush();
    }
}