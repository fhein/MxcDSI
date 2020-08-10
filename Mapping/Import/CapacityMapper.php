<?php


namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Product;

class CapacityMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    /** @var array */
    protected $config;
    /**
     * DosageMapper constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $type = $product->getType();
        if (! in_array($type, ['AROMA', 'SHAKE_VAPE', 'NICSALT_LIQUID', 'LIQUID', 'LIQUID_BOX', 'BASE', 'EASY3_CAP'])) return;

        $capacity = NULL;
        if (in_array($type, ['NICSALT_LIQUID', 'LIQUID', 'LIQUID_BOX', 'BASE'])) {
            $variants = $product->getVariants();
            foreach ($variants as $variant) {
                $capacity = $variant->getContent();
                $variant->setCapacity($capacity);
            }
        } else {
            $icNumber = $product->getIcNumber();
            $capacity = @$this->config[$icNumber]['capacity'];
            $capacity = $capacity ?? $this->remapCapacity($product);

            $variants = $product->getVariants();
            /** @var Variant $variant */
            foreach ($variants as $variant) {
                $variant->setCapacity($capacity);
            }
        }

        $product->setCapacity($capacity);
    }

    protected function remapContent(Product $product)
    {
        $name = $product->getName();
        $content = null;
        $matches = [];
        if (preg_match('~(\d+) ?ml~', $name, $matches) === 1) {
            $content = $matches[1];
        }
        return $content;
    }

    protected function remapCapacity(Product $product)
    {
        $capacity = null;
        $description = $product->getIcDescription();
        if (preg_match('~(\d+) ?ml Flasche~', $description, $matches) === 1) {
            $capacity = $matches[1];
        }
        return $capacity;
    }

    public function report()
    {
    }
}