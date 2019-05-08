<?php


namespace MxcDropshipInnocigs\Mapping\Check;


use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;

class MappingConsistency implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    protected $models;

    public function check()
    {
        $products = $this->modelManager->getRepository(Product::class)->findAll();
        $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
        $issues = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $issue = $this->getMappingIssues($product);
            if (! empty($issue)) {
                $issues[$product->getIcNumber()] = $issue;
            }
        }
        ksort($issues);
        (new ArrayReport())(['pmVariantsWithoutModels', $issues]);
    }

    public function getMappingIssues(Product $product): array
    {
        $variants = $product->getVariants();
        $map = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $number = $variant->getIcNumber();
            $model = $this->models[$number] ?? null;
            if (! $model) $map[] = $number;
        }
        sort($map);
        return $map;
    }

}