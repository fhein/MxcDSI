<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Excel;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;
use MxcDropshipIntegrator\Mapping\Shopware\PriceEngine;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\TaxTool;
use MxcCommons\Defines\Constants;

class ImportPrices extends AbstractProductImport implements AugmentedObject
{
    use LoggerAwareTrait;

    /** @var array */
    protected $indexMap;

    /** @var array */
    protected $models;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    /** @var PriceEngine */
    protected $priceEngine;

    public function __construct(
        PriceMapper $priceMapper,
        PriceEngine $priceEngine
    ) {
        $this->priceMapper = $priceMapper;
        $this->priceEngine = $priceEngine;
    }

    public function processImportData(array &$data)
    {
        $keys = array_keys($data[0]);
        $this->indexMap = [];
        foreach ($keys as $key) {
            if (strpos($key, 'VK Brutto') === 0) {
                $customerGroupKey = explode(' ', $key)[2];
                $this->indexMap[$key] = $customerGroupKey;
            }
        }

        $variants = $this->modelManager->getRepository(Variant::class)->getAllIndexed();
        $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
        /** @var Variant $variant */

        foreach ($data as $record) {
            $variant = $variants[$record['icNumber']] ?? null;
            if (!$variant) continue;
            $variant->setRetailPriceDampfPlanet(StringTool::tofloat($record['Dampfplanet']));
            $variant->setRetailPriceMaxVapor(StringTool::tofloat($record['MaxVapor']));
            $variant->setRetailPriceOthers(StringTool::tofloat($record['andere']));
            $this->updateVariantPrice($variant, $record);
        }
        $this->modelManager->flush();
        ApplyPriceRules::run();
    }

    protected function updateVariantPrice(Variant $variant, array $record)
    {
        $customerPrice = $record['VK Brutto EK'];
        // if no price is specified we take the UVP
        if (empty($customerPrice)) $customerPrice = $record['UVP Brutto'];
        $customerPrice = $customerPrice === '' ? null : $customerPrice;

        $vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;
        $prices = [];
        foreach ($this->indexMap as $column => $customerGroup) {
            $price = $record[$column];
            $price = $price === '' ? null : $price;
            $price = $price ?? $customerPrice;
            if (! $price) continue;

            $netPrice = StringTool::tofloat($price) / $vatFactor;
            $prices[$customerGroup] = $netPrice;
        }
        $this->priceEngine->setRetailPrices($variant, $prices);
        $this->priceMapper->setPrices($variant);
    }
}