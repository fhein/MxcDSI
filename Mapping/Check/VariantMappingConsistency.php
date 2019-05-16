<?php


namespace MxcDropshipInnocigs\Mapping\Check;


use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;

class VariantMappingConsistency implements LoggerAwareInterface, ModelManagerAwareInterface
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