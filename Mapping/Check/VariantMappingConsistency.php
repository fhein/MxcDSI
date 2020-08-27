<?php

namespace MxcDropshipIntegrator\Mapping\Check;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Report\ArrayReport;

class VariantMappingConsistency implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    public function check()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $variants = $this->modelManager->getRepository(Variant::class)->getVariantsWithoutModel();
        /** @var Variant $variant */
        $issues = [];

        $count = count($variants);
        if ($count === 0) return 0;

        foreach ($variants as $variant) {
            $issues[$variant->getProduct()->getIcNumber()][] = $variant->getIcNumber();
        }
        ksort($issues);
        (new ArrayReport())(['pmVariantMappingIssues' => $issues]);
        return $count;
    }
}