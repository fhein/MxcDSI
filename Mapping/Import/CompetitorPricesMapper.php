<?php


namespace MxcDropshipInnocigs\Mapping\Import;


use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class CompetitorPricesMapper implements ProductMapperInterface
{
    /** @var array */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function map(Model $model, Product $product)
    {
        $retailPriceDampfplanet = @$this->config[$product->getIcNumber()]['retailPriceDampfplanet'];
        if ($retailPriceDampfplanet !== null) {
            $product->setRetailPriceDampfPlanet($retailPriceDampfplanet);
        }
        $retailPriceOthers = @$this->config[$product->getIcNumber()]['retailPriceOthers'];
        if ($retailPriceOthers !== null) {
            $product->setRetailPriceOthers($retailPriceOthers);
        }
    }

    public function report()
    {
        // TODO: Implement report() method.
    }
}