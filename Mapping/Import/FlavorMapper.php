<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class FlavorMapper implements ProductMapperInterface
{
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
        $flavor = @$this->config[$product->getIcNumber()];
        if (! $flavor) return;

        $flavor = explode(',', $this->config[$product->getIcNumber()]['flavor']);
        $flavor = array_map('trim', $flavor);
        $flavor = implode(', ', $flavor);
        $product->setFlavor($flavor);
    }}