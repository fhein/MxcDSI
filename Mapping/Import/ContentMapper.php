<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class ContentMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface
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

    /**
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product)
    {

        $type = $product->getType();
        if (! in_array($type, ['AROMA', 'SHAKE_VAPE', 'LIQUID'])) return;
        $icNumber = $product->getIcNumber();

        $content = $this->config[$icNumber]['content'] ?? null;
        $capacity = $this->config[$icNumber]['content'] ?? null;

        if (! $content) {
            $name = $product->getName();
            $matches = [];

            if (preg_match('~(\d+) ?ml~', $name, $matches) === 1) {
                $content = $matches[1];
            }
        }

        if (! $capacity && $type === 'LIQUID') {
            $capacity = $content;
        }

        if (! $capacity) {
            $description = $product->getIcDescription();
            if (preg_match('~(\d+) ?ml Flasche~', $description, $matches) === 1) {
                $capacity = $matches[1];
            }
        }

        $product->setCapacity($capacity);
        $product->setContent($content);
    }

    public function report()
    {
    }
}