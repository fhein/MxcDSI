<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\TaxTool;
use MxcDropshipIntegrator\Models\Product;

class BulkPriceMapper implements AugmentedObject
{
    use ModelManagerAwareTrait;

    protected $bulkPrices = [
        'LIQUID' => [
            'SC' =>
            [
                '1er Packung' => [
                    'EK' => 1.95,
                ],
                '10er Packung' => [
                    'EK' => 17.5,
                ],
            ],
            'InnoCigs' => [
                '1er Packung' => [
                    'EK' => 2.45,
                ],
                '10er Packung'  => [
                    'EK' => 21.5,
                ],
            ],
            'Erste Sahne' =>
            [
                '1er Packung' => [
                    'EK' => 3.75,
                ],
                '5er Packung'  => [
                    'EK' => 17.8,
                ],
                '10er Packung' => [
                    'EK' => 33.75,
                ],
            ],
            'Vampire Vape' => [
                '1er Packung' => [
                    'EK' => 3.7
                ],
                '20er Packung' => [
                    'EK' => 69.9,
                ],
            ],
        ],
    ];

    /** @var PriceEngine */
    protected $priceEngine;

    /** @var PriceMapper */
    protected $priceMapper;

    protected $vatFactor;

    public function __construct(PriceEngine $priceEngine, PriceMapper $priceMapper)
    {
        $this->priceEngine = $priceEngine;
        $this->priceMapper = $priceMapper;
        $this->vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;
    }

    public function mapBulkPrices()
    {
        $repository = $this->modelManager->getRepository(Product::class);
        foreach ($this->bulkPrices as $type => $data) {
            foreach ($data as $brand => $prices) {
                $products = $repository->findBy(['type' => $type, 'brand' => $brand]);
                /** @var Product $product */
                foreach ($products as $product) {
                    $this->mapPrices($product, $prices);
                }
                $this->modelManager->flush();
            }
        }
    }

    public function mapPrices(Product $product, array $priceList)
    {
        $variants = $product->getVariants();
        $modified = false;
        foreach ($variants as $variant) {
            $options = $variant->getOptions();
            foreach ($options as $option) {
                $groupName = $option->getIcGroup()->getName();
                if ($groupName != 'Packungsgröße') continue;
                foreach ($priceList as $optionName => $prices) {
                    if ($optionName != $option->getName()) continue;
                    foreach ($prices as $customerGroup => $price) {
                        $this->priceEngine->setRetailPrice($variant, $customerGroup, $price / $this->vatFactor);
                    }
                }
                $this->priceMapper->setPrices($variant);
            }
        }
    }

}