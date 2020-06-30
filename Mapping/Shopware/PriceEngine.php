<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Toolbox\Shopware\TaxTool;
use Shopware\Models\Customer\Group;
use Zend\Config\Factory;

class PriceEngine implements LoggerAwareInterface, ModelManagerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;

    private $report;

    protected $defaultConfig = [
        'price' => null,
        'margin_min_percent'    => 25,
        'margin_min_abs'        => 1,
        'margin_max_percent'    => null,
        'margin_max_abs'        => 15,
    ];

    protected $configFile = __DIR__ . '/../../Config/PriceEngine.config.php';

    protected $config;

    private $customerGroups = null;

    /**
     * Liefert ein Array mit allen Schlüsseln der Shopware Kundengruppen (EK, H, usw.)
     *
     * @return array
     */
    public function getCustomerGroupKeys(): array
    {
        return array_keys($this->getCustomerGroups());
    }

    public function createDefaultConfiguration()
    {
        $config = [];
        $config['_default'] = $this->defaultConfig;
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
        /** @var Product $product */
        foreach ($products as $product) {
            $type = $product->getType();
            if (empty($config[$type]['_default'])) {
                $config[$type]['_default'] = $this->defaultConfig;
            }
//            $supplier = $product->getSupplier();
//            if ($supplier === 'InnoCigs') {
//                $supplier = $product->getBrand();
//            }
//            if (empty($config[$type][$supplier]['_default'])) {
//                $config[$type][$supplier]['_default'] = $this->defaultConfig;
//            }
        }
        Factory::toFile($this->configFile, [ 'rules' => $config]);

    }

    public function getCorrectedRetailPrices(Variant $variant)
    {
        $priceConfig = $this->getPriceConfig($variant);
        $retailPrices = $this->getRetailPrices($variant);

        $correctedPrices = [];
        // $log = [];
        $purchasePrice = floatval($variant->getPurchasePrice());
        foreach ($retailPrices as $key => $retailPrice) {
            [$newRetailPrice, $log] = $this->correctPrice($purchasePrice, $retailPrice, $priceConfig);
            $correctedPrices[$key] = $newRetailPrice;
            if ($retailPrice !== $newRetailPrice) {
                $this->report[] = [
                    'name' => $variant->getName(),
                    'purchasePrice' => $purchasePrice,
                    'oldRetailPrice' => $retailPrice,
                    'newRetailPrice' => $newRetailPrice,
                    'priceConfig'    => $priceConfig,
                    'log'  => $log,
                ];
            }
        }
        // $report = new ArrayReport();
        // $report(['pePriceCorrections' => $this->report]);

        return $correctedPrices;
    }

    protected function getDiscount(float $grossRetailPrice) {
        $discounts = @$this->classConfig['discounts'];
        $result = 0;
        if ($discounts === null) return $result;
        foreach ($discounts as $discount) {
            if ($grossRetailPrice > $discount['price']) {
                $result = $discount['discount'];
            }
        }
        return $result;
    }

    public function beautifyPrice(float $grossRetailPrice)
    {
        // Rundung des Kundenverkaufspreises auf 5 Cent
        $grossRetailPrice = round($grossRetailPrice / 0.05) * 0.05;

        // x,05 Preise um 10 Cent senken
        // x.00 Preise um 5 Cent senken
        $fraction = round($grossRetailPrice - floor($grossRetailPrice), 2);
        $adjustment = 0.0;
        if ($fraction == 0.05) {
            $adjustment = 0.10;
        } elseif ($fraction == 0.0) {
            $adjustment = 0.05;
        }

        return $grossRetailPrice - $adjustment;
    }

    protected function correctPrice(float $netPurchasePrice, float $netRetailPrice, array $priceConfig) : array
    {
        $log = [];

        $vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;

        // Ist die minimale prozentuale Marge nicht erreicht, wird der Netto-Verkaufspreis erhöht
        // und die Marge neu berechnet
        $minMarginPercent = $priceConfig['margin_min_percent'];
        if ($minMarginPercent !== null && round($margin,5) < $minMarginPercent) {
            $netRetailPrice = $netPurchasePrice / (1 - ($minMarginPercent / 100));
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            // $log[] = 'Minimum margin adjusted to ' . $minMarginPercent . '%.';
        };

        // Ist die maximale prozentuale Marge überschritten, wird der Netto-Verkaufspreis gesenkt
        $maxMarginPercent = $priceConfig['margin_max_percent'];
        if ($maxMarginPercent !== null && $margin > $maxMarginPercent) {
            $netRetailPrice = $netPurchasePrice / (1 - ($maxMarginPercent / 100));
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            // $log[] = 'Maximim margin adjusted to ' . $maxMarginPercent . '%.';
        }

        // Ist die maximale absolute Marge in EUR (dennoch) überschritten, wird der Netto-Verkaufspreis gesenkt
        // und die Marge neu berechnet
        $limit = $priceConfig['margin_max_abs'];
        $marginAbsolute = round($netRetailPrice - $netPurchasePrice, 5);
        if ($limit !== null && $marginAbsolute > $limit) {
            $netRetailPrice = $netPurchasePrice + $limit;
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            // $log[] = 'Maximum absolute margin adjusted to ' . $limit . ' EUR (from ' . (round($marginAbsolute, 2)) . ' EUR). New margin: ' . round($margin, 2) . '.';
        }

        $discount = $this->getDiscount($netRetailPrice * $vatFactor) / 100;

        if ($discount != 0) {
            $discountValue = $netRetailPrice * $discount;
            $discountedNetRetailPrice = $netRetailPrice - $discountValue;

            $netRevenue = $discountedNetRetailPrice - $netPurchasePrice;
            $dropShip = 6.12;
            // Dropship Versandkosten
            $netRevenue -= $dropShip;

            if ($netRevenue < 6.0) {
                $minRevenue = $discountValue + (6.0 + $dropShip) / 0.925;
                $netRetailPrice = $netPurchasePrice + $minRevenue;
                $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
                // $log[] = 'Minimum revenue adjusted to ' . round($minRevenue,2) . '. New margin: ' . round($margin, 2). '.';
            }
        }

        // Ist die minimale absolute Marge in EUR nicht erreicht, wird der Netto-Verkaufspreis erhöht
        // und die Marge neu berechnet
        $limit = $priceConfig['margin_min_abs'];
        if ($limit !== null && floatval($netRetailPrice - $netPurchasePrice) < $limit) {
            $netRetailPrice = $netPurchasePrice + $limit;
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            // $log[] = 'Minimum absolute margin adjusted to ' . $limit . ' EUR. New margin: ' . round($margin, 2). '.';
        }

        return [ $netRetailPrice, $log ];
    }

    public function report()
    {
        $report = new ArrayReport;
        $report(['pePriceCorrections' => $this->report]);
        $this->report = [];
    }

    protected function getPriceConfig(Variant $variant)
    {
        $product = $variant->getProduct();
        $type = $product->getType();
        $supplier = $product->getSupplier();
        $variantNumber = $variant->getIcNumber();
        $productNumber = $product->getIcNumber();
        if ($supplier === 'InnoCigs') $supplier = $product->getBrand();

        $rules = $this->classConfig['rules'];

        $config = null;
        $config = @$rules[$type][$supplier][$productNumber][$variantNumber];
        if ($config !== null) return $config;
        $config = @$rules[$type][$supplier][$productNumber]['_default'];
        if ($config !== null) return $config;
        $config = @$rules[$type][$supplier]['_default'];
        if ($config !== null) return $config;
        $config = @$rules[$type]['_default'];
        if ($config !== null) return $config;
        $config = $this->defaultConfig;
        return $config;
    }

    /**
     * Liefert ein Array der Shopware Kundengruppen indiziert nach den Schlüsseln (EK, H, usw.)
     *
     * @return array
     */
    public function getCustomerGroups(): array
    {
        if ($this->customerGroups !== null) return $this->customerGroups;
        $customerGroups = $this->modelManager->getRepository(Group::class)->findAll();
        /** @var Group $customerGroup */
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroups[$customerGroup->getKey()] = $customerGroup;
        }
        return $this->customerGroups;
    }

    public function setRetailPrices(Variant $variant, array $grossRetailPrices)
    {
        $prices = [];
        foreach ($grossRetailPrices as $key => $price) {
            $prices[] = $key . MxcDropshipInnocigs::MXC_DELIMITER_L1 . $price;
        }
        $grossRetailPrices = implode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $prices);
        $variant->setRetailPrices($grossRetailPrices);
    }

    public function getRetailPrices(Variant $variant)
    {
        $retailPrices = [];
        /** @var Variant $variant */
        $sPrices = explode(MxcDropshipInnocigs::MXC_DELIMITER_L2, $variant->getRetailPrices());
        foreach ($sPrices as $sPrice) {
            [$key, $price] = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $sPrice);
            $retailPrices[$key] = floatVal(str_replace(',', '.', $price));
        }
        return $retailPrices;
    }
}