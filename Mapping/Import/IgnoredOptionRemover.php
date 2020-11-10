<?php


namespace MxcDropshipIntegrator\Mapping\Import;


use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;

class IgnoredOptionRemover extends BaseImportMapper implements AugmentedObject
{
    protected $ignore = [
        'options' => [

        ],
        'groups'  => [
            // Vavo 10 ml Nikotinshot, ignore Nikotinstärke, because it constantly 18 mg
            'VF100L10-S' => [
                'Nikotinstärke',
            ],
        ],
    ];

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $productNumber = $product->getIcNumber();
        if (! in_array($productNumber, array_keys($this->ignore['groups']))) {
            return;
        }
        $ignoredGroups = $this->ignore['groups'][$productNumber];
        if (empty ($ignoredGroups)) {
            return;
        }
        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $options = $variant->getOptions();
            /** @var Option $option */
            foreach ($options as $option) {
                $groupName = $option->getIcGroup()->getName();
                if (in_array($groupName, $ignoredGroups)) {
                    $variant->removeOption($option);
                }
            }
        }
    }

    public function report()
    {}
}