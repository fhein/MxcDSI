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
                    'EK' => 17.95,
                ],
            ],
            'InnoCigs' => [
                '1er Packung' => [
                    'EK' => 2.45,
                ],
                '10er Packung'  => [
                    'EK' => 22.9,
                ],
            ],
            'Erste Sahne' =>
            [
                '1er Packung' => [
                    'EK' => 3.75,
                ],
                '5er Packung'  => [
                    'EK' => 18.25,
                ],
                '10er Packung' => [
                    'EK' => 34.60,
                ],
            ],
            'Vampire Vape' => [
                '1er Packung' => [
                    'EK' => 3.8
                ],
                '20er Packung' => [
                    'EK' => 69.9,
                ],
            ],
        ],
        'NICSALT_LIQUID' => [
            'Pod Salt Fusion' =>
                [
                    '1er Packung' => [
                        'EK' => 5.40,
                    ],
                    '5er Packung' => [
                        'EK' => 24.30,
                    ],
                ],
        ]
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
            }
        }
        $this->modelManager->flush();
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
                        $variant->setAccepted(true);
                    }
                }
                $this->priceMapper->setPrices($variant);
                $this->priceMapper::setReferencePriceVariant($variant, 100);
            }
        }
    }
}