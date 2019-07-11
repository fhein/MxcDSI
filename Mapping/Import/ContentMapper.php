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

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $type = $product->getType();
        if (! in_array($type, ['AROMA', 'SHAKE_VAPE', 'LIQUID'])) return;

        $icNumber = $product->getIcNumber();
        $content = @$this->config[$icNumber]['content'];
        $capacity = @$this->config[$icNumber]['capacity'];

        if ($content === null) {
            $content = $this->remapContent($product);
        }

        if ($capacity === null) {
            $capacity = $type === 'LIQUID' ? $content : $this->remapCapacity($product);
        }

        $product->setContent($content);
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