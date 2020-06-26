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
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Toolbox\Shopware\PriceTool;
use MxcDropshipInnocigs\Toolbox\Shopware\TaxTool;
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

    /**
     * @var PriceTool $priceTool
     */
    protected $priceTool;

    public function __construct(PriceTool $priceTool)
    {
        $this->priceTool = $priceTool;
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
        $retailPrices = $this->priceTool->getRetailPrices($variant);

        $correctedPrices = [];
        $log = [];
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
        $report = new ArrayReport();
        $report(['pePriceCorrections' => $this->report]);

        return $correctedPrices;
    }

    protected function getNetDiscount(float $netRetailPrice) {
        $vatFactor = TaxTool::getCurrentVatPercentage() / 100;
        $grossRetailPrice = $netRetailPrice / ( 1 + $vatFactor);

        $discounts = @$this->classConfig['discounts'];
        if ($discounts === null) return 0;
        foreach ($discounts as $discount) {
            if ($grossRetailPrice > $discount['price']) {
                $discount = $discount['discount'];
            }
        }
        return $discount;
    }

    protected function correctPrice(float $netPurchasePrice, float $grossRetailPrice, array $priceConfig)
    {
        $log = [];
        $netRetailPrice = $grossRetailPrice / ( 1 + TaxTool::getCurrentVatPercentage() / 100);
        $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;

        // Ist die minimale prozentuale Marge nicht erreicht, wird der Netto-Verkaufspreis erhöht
        // und die Marge neu berechnet
        $minMarginPercent = $priceConfig['margin_min_percent'];
        if ($minMarginPercent !== null && $margin < $minMarginPercent) {
            $netRetailPrice = $netPurchasePrice / (1 - ($minMarginPercent / 100));
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            $log[] = 'Minimum margin adjusted to ' . $minMarginPercent . '%.';
        };

        // Ist die minimale absolute Marge in EUR nicht erreicht, wird der Netto-Verkaufspreis erhöht
        // und die Marge neu berechnet
        $limit = $priceConfig['margin_min_abs'];
        if ($limit !== null && $netRetailPrice - $netPurchasePrice < $limit) {
            $netRetailPrice = $netPurchasePrice + $limit;
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            $log[] = 'Minimum absolute margin adjusted to ' . $limit . ' EUR.';
        }

        // Ist die maximale prozentuale Marge überschritten, wird der Netto-Verkaufspreis gesenkt
        $maxMarginPercent = $priceConfig['margin_max_percent'];
        if ($maxMarginPercent !== null && $margin > $maxMarginPercent) {
            $netRetailPrice = $netPurchasePrice / (1 - ($maxMarginPercent / 100));
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            $log[] = 'Maximim margin adjusted to ' . $maxMarginPercent . '%.';
        }

        // Ist die maximale absolute Marge in EUR (dennoch) überschritten, wird der Netto-Verkaufspreis gesenkt
        // und die Marge neu berechnet
        $limit = $priceConfig['margin_max_abs'];
        if ($limit !== null && $netRetailPrice - $netPurchasePrice > $limit) {
            $netRetailPrice = $netPurchasePrice + $limit;
            $margin = ($netRetailPrice - $netPurchasePrice) / $netRetailPrice * 100;
            $log[] = 'Maximum absolute margin adjusted to ' . $limit . ' EUR.';
        }

        // Stelle einen mininalen Ertrag von 6 EUR sicher, auch dann, wenn das Produkt rabattiert wird, weil es
        // mehr als 75 EUR kostet
        $discount = $this->getNetDiscount($netRetailPrice) / 100;
        if ($discount != 0) {
            $discountValue = $netRetailPrice * $discount;
            $discountedNetRetailPrice = $netRetailPrice - $discountValue;

            $netRevenue = $discountedNetRetailPrice - $netPurchasePrice;
            // Dropship Versandkosten
            $netRevenue -= 6.12;

            if ($netRevenue < 6.0) {
                $netRetailPrice = $netPurchasePrice + $discountValue + 6.0 / $discount + 6.12;
                $log[] = 'Minimum revenue adjusted to ';
            }
        }

        $vatFactor = TaxTool::getCurrentVatPercentage() / 100;
        $newGrossRetailPrice = $netRetailPrice * (1 + $vatFactor);

        // Rundung des Kundenverkaufspreises auf 5 Cent
        $newGrossRetailPrice = round($newGrossRetailPrice / 0.05) * 0.05;

        // Hier könnten noch weitere psychogische Preisverschönerungen durchgeführt werden
        
        return [ $newGrossRetailPrice, $log ];
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
}