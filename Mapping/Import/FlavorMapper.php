<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class FlavorMapper extends BaseImportMapper implements ProductMapperInterface
{
    /**
     * FlavorMapper constructor.
     *
     * @param ImportMappings $importMapping
     * @param LoggerInterface $log
     */
    public function __construct(ImportMappings $importMapping, LoggerInterface $log)
    {
        parent::__construct($importMapping->getConfig(), $log);
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

        $flavor = explode(',', $this->config[$product->getIcNumber()]['flavor']);
        $flavor = array_map('trim', $flavor);
        $flavor = implode(', ', $flavor);
        $product->setFlavor($flavor);
    }}