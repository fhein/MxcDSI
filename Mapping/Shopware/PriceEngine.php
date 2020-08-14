<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcCommons\Toolbox\Report\ArrayReport;
use MxcCommons\Toolbox\Shopware\TaxTool;
use Shopware\Models\Customer\Group;
use MxcCommons\Defines\Constants;

class PriceEngine implements LoggerAwareInterface, ModelManagerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;

    private $report;

    protected $logEnabled = false;

    protected $configFile = __DIR__ . '/../../Config/PriceEngine.config.php';

    protected $config;

    private $customerGroups = null;

    // Wenn das Log eingeschaltet ist, werden die Modifikationen der PriceEngine detailliert in die Datei
    // pe_price_corrections.php protokolliert. Dies führt zu einer massiven Verlängerung der Laufzeit und
    // sollte daher nur für das Debugging aktiviert werden.
    //
    public function enableLog(bool $logEnabled = true)
    {
        $this->logEnabled = $logEnabled;
    }

    /**
     * Liefert ein Array mit allen Schlüsseln der Shopware Kundengruppen (EK, H, usw.)
     *
     * @return array
     */
    public function getCustomerGroupKeys(): array
    {
        return array_keys($this->getCustomerGroups());
    }

    public function getCorrectedRetailPrices(Variant $variant)
    {
        $priceConfig = $this->getPriceConfig($variant);
        $retailPrices = $this->getRetailPrices($variant);

        $correctedPrices = [];
        $log = [];
        $purchasePrice = floatval($variant->getPurchasePrice());
        foreach ($retailPrices as $key => $retailPrice) {
            [$newRetailPrice, $log] = $this->correctPrice($purchasePrice, $retailPrice, $priceConfig);
            $correctedPrices[$key] = $newRetailPrice;
            if ($retailPrice !== $newRetailPrice) {
                if ($this->logEnabled) {
                    $this->report[] = [
                        'name'           => $variant->getName(),
                        'purchasePrice'  => $purchasePrice,
                        'oldRetailPrice' => $retailPrice,
                        'newRetailPrice' => $newRetailPrice,
                        'priceConfig'    => $priceConfig,
                        'log'            => $log,
                    ];
                }
            }
        }
        if ($this->logEnabled) {
            $report = new ArrayReport();
            $report(['pePriceCorrections' => $this->report]);
        }

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
            if ($this->logEnabled) {
                $log[] = 'Minimum margin adjusted to ' . $minMarginPercent . '%.';
            }
        }

        // Ist die maximale prozentuale Marge überschritten, wird der Netto-Verkaufspreis gesenkt
        $maxMarginPercent = $priceConfig['margin_max_percent'];
        if ($maxMarginPercent !== null && $margin > $maxMarginPercent) {
            $netRetailPrice = $netPurchasePrice / (1 - ($maxMarginPercent / 100));
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            if ($this->logEnabled) {
                $log[] = 'Maximim margin adjusted to ' . $maxMarginPercent . '%.';
            }
        }

        // Ist die maximale absolute Marge in EUR (dennoch) überschritten, wird der Netto-Verkaufspreis entsprechend
        // gesenkt, die Paypal-Kosten werden aufgeschlagen, und die Marge neu berechnet
        $limit = $priceConfig['margin_max_abs'];
        $marginAbsolute = round($netRetailPrice - $netPurchasePrice, 5);
        if ($limit !== null && $marginAbsolute > $limit) {
            $paypalFactor = 1.03;
            $netRetailPrice = ($netPurchasePrice + $limit) * $paypalFactor;
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            if ($this->logEnabled) {
                $log[] = 'Maximum absolute margin adjusted to ' . $limit . ' EUR (from ' . (round($marginAbsolute,
                        2)) . ' EUR). New margin: ' . round($margin, 2) . '.';
            }
        }

        $discount = $this->getDiscount($netRetailPrice * $vatFactor) / 100;

        $marginMinDiscountAbs = @$priceConfig['margin_min_discount_abs'];
        if ($discount != 0 && $marginMinDiscountAbs != null) {
            $discountValue = $netRetailPrice * $discount;
            $discountedNetRetailPrice = $netRetailPrice - $discountValue;

            $netRevenue = $discountedNetRetailPrice - $netPurchasePrice;
            $dropShip = 6.12;
            // Dropship Versandkosten
            $netRevenue -= $dropShip;

            if ($netRevenue < $marginMinDiscountAbs) {
                $minRevenue = $discountValue + ($marginMinDiscountAbs + $dropShip) / 0.925;
                $paypalFactor = 1.03;
                $netRetailPrice = ($netPurchasePrice + $minRevenue) * $paypalFactor;
                $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
                if ($this->logEnabled) {
                    $log[] = 'Minimum revenue adjusted to ' . round($minRevenue, 2) . '. New margin: '
                             . round($margin,2) . '.';
                }
            }
        }

        // Ist die minimale absolute Marge in EUR nicht erreicht, wird der Netto-Verkaufspreis erhöht
        // und die Marge neu berechnet
        $limit = $priceConfig['margin_min_abs'];
        if ($limit !== null && floatval($netRetailPrice - $netPurchasePrice) < $limit) {
            $netRetailPrice = $netPurchasePrice + $limit;
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            if ($this->logEnabled) {
                $log[] = 'Minimum absolute margin adjusted to ' . $limit . ' EUR. New margin: ' . round($margin,
                        2) . '.';
            }
        }

        return [ $netRetailPrice, $log ];
    }

    public function report()
    {
        if ($this->logEnabled) {
            $report = new ArrayReport;
            $report(['pePriceCorrections' => $this->report]);
            $this->report = [];
        }
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

        $config = $rules['_default'];
        $cursor = @$rules[$type]['_default'];
        if ($cursor != null) $config = array_merge($config, $cursor);
        $cursor = @$rules[$type][$supplier]['_default'];
        if ($cursor != null) $config = array_merge($config, $cursor);
        $cursor = @$rules[$type][$supplier][$productNumber]['_default'];
        if ($cursor != null) $config = array_merge($config, $cursor);
        $cursor = @$rules[$type][$supplier][$productNumber][$variantNumber];
        if ($cursor != null) $config = array_merge($config, $cursor);

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
            $prices[] = $key . Constants::DELIMITER_L1 . $price;
        }
        $grossRetailPrices = implode(Constants::DELIMITER_L2, $prices);
        $variant->setRetailPrices($grossRetailPrices);
    }

    public function getRetailPrices(Variant $variant)
    {
        $retailPrices = [];
        /** @var Variant $variant */
        $sPrices = explode(Constants::DELIMITER_L2, $variant->getRetailPrices());
        foreach ($sPrices as $sPrice) {
            [$key, $price] = explode(Constants::DELIMITER_L1, $sPrice);
            $retailPrices[$key] = StringTool::tofloat($price);
        }
        return $retailPrices;
    }
}