<?php

namespace MxcDropshipIntegrator\Jobs;

use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipIntegrator\Mapping\Shopware\PriceEngine;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

/**
 * This job checks all prices against the pricing rules using the PriceEngine
 * and updates the prices accordingly in both products and articles
 */
class ApplyPriceRules
{
    private static $log;

    public static function run()
    {
        $services = MxcDropshipIntegrator::getServices();
        $modelManager = $services->get('models');
        self::$log = $services->get('logger');
        /** @var PriceEngine $priceEngine */
        $priceEngine = $services->get(PriceEngine::class);
        /** @var PriceMapper $priceMapper */
        $priceMapper = $services->get(PriceMapper::class);
        $variants = $modelManager->getRepository(Variant::class)->findAll();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            // Keine Preiskorrektur für das Buch 'Die E-Zigarette - Fakten und Mythen'
            if ($variant->getIcNumber() === 'B100EZ-10') continue;
            $retailPrices = $priceEngine->getRetailPrices($variant);
            $correctedPrices = $priceEngine->getCorrectedRetailPrices($variant);
            $priceEngine->setRetailPrices($variant, $correctedPrices);
            $priceMapper->setPrices($variant);
            self::logPriceChanges($variant, $retailPrices, $correctedPrices);
        }
        $modelManager->flush();
        self::$log->debug('Price rules successfully applied.');
    }

    protected static function logPriceChanges(Variant $variant, array $oldPrices, array $newPrices)
    {
        $purchasePrice = $variant->getPurchasePrice();
        foreach ($oldPrices as $key => $oldPrice)  {
            $newPrice = $newPrices[$key];
            if (round($oldPrice,2) != round($newPrice, 2)) {
                $oldPrice = StringTool::tofloat($oldPrice);
                $newPrice = StringTool::tofloat($newPrice);
                $margin = ($newPrice - $purchasePrice) / $newPrice * 100;
                $msg1 = sprintf('Price change: %s (%s)', $variant->getName(), $variant->getIcNumber());
                $msg2 = sprintf( '   Group %s: Old price: %.2f. New price: %.2f. New margin %.2f%%.',
                    $key,
                    $oldPrice,
                    $newPrice,
                    $margin
                );
                self::$log->info($msg1);
                self::$log->info($msg2);
            }
        }
    }
}