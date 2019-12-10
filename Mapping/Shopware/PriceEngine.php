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
use Zend\Config\Factory;

class PriceEngine implements LoggerAwareInterface, ModelManagerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;

    protected $defaultConfig = [
        'price' => null,
        'margin_min' => 19,
        'margin_target' => 25,
        'margin_absolut_max' => 12,
    ];

    protected $configFile = __DIR__ . '/../../Config/PriceEngine.config.php';

    protected $config;

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
            $supplier = $product->getSupplier();
            if ($supplier === 'InnoCigs') {
                $supplier = $product->getBrand();
            }
            if (empty($config[$type][$supplier]['_default'])) {
                $config[$type][$supplier]['_default'] = $this->defaultConfig;
            }
        }
        Factory::toFile($this->configFile, $config);

    }

    protected function getPriceConfig(Variant $variant)
    {
        $product = $variant->getProduct();
        $type = $product->getType();
        $supplier = $product->getSupplier();
        $variantNumber = $variant->getIcNumber();
        $productNumber = $product->getIcNumber();
        if ($supplier === 'InnoCigs') $supplier = $product->getBrand();

        $config = @$this->classConfig[$type][$supplier][$productNumber][$variantNumber];
        if ($config !== null) return $config;
        $config = @$this->classConfig[$type][$supplier][$productNumber]['_default'];
        if ($config !== null) return $config;
        $config = @$this->classConfig[$type][$supplier]['_default'];
        if ($config !== null) return $config;
        $config = @$this->classConfig[$type]['_default'];
        if ($config !== null) return $config;
        $config = $this->defaultConfig;
        return $config;
    }
}