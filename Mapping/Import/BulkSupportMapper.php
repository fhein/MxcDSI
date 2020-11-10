<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;

// enables bulk containers for liquids
class BulkSupportMapper extends BaseImportMapper
{
    protected $typesSupportingBulkPackages = [
//        'LIQUID',
//        'NICSALT_LIQUID',
        'SHOT',
    ];

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $type = $product->getType();
        if (! in_array($type, $this->typesSupportingBulkPackages)) return;
        $variants = $product->getVariants();
        /** @var Variant $variant */
        // we we allow bulk containers up to 20 pieces per pack
        foreach ($variants as $variant) {
            $options = $variant->getOptions();
            /** @var Option $option */
            foreach ($options as $option) {
                $group = $option->getIcGroup();
                if ($group->getName() !== 'Packungsgröße') continue;
                $optionName = $option->getName();
                $size = [];
                preg_match('~(\d+)er Packung~', $optionName, $size);
                $size = intval($size[1]);
                if ($size > 20) continue;
                $variant->setAccepted(true);
            }
            $accepted = $variant->getAccepted();
        }
    }

    public function map2(Model $model, Product $product, bool $remap = false)
    {
        $variants = $product->getVariants();
        /** @var Variant $variant */
        // we we allow bulk containers up to 20 pieces per pack
        foreach ($variants as $variant) {
            $options = $variant->getOptions();
            $variant->setAccepted(false);
            /** @var Option $option */
            foreach ($options as $option) {
                $group = $option->getIcGroup();
                if ($group->getName() !== 'Packungsgröße') continue;
                $optionName = $option->getName();
                $size = [];
                preg_match('~(\d+)er Packung~', $optionName, $size);
                $size = intval($size[1]);
                if ($size != 1) continue;
                $variant->setAccepted(true);
            }
            $accepted = $variant->getAccepted();
        }
    }

    public function report()
    {}

}