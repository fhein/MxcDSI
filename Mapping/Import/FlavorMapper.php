<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;

class FlavorMapper implements ProductMapperInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;
    /**
     * FlavorMapper constructor.
     *
     * @param ImportMappings $importMapping
     */

    /** @var array */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Assign the product flavor from article configuration.
     *
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product)
    {
        if ($product->getFlavor() !== null) return;
        $flavor = @$this->config[$product->getIcNumber()]['flavor'];
        if (! $flavor) return;

        $flavor = explode(',', $flavor);
        $flavor = array_map('trim', $flavor);
        $flavor = implode(', ', $flavor);
        $product->setFlavor($flavor);
    }

    public function report()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $missingFlavors = $this->modelManager->getRepository(Product::class)->getProductsWithFlavorMissing();
        (new ArrayReport())(['pmMissingFlavors' => $missingFlavors]);
    }
}
