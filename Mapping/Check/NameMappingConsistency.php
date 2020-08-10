<?php


namespace MxcDropshipIntegrator\Mapping\Check;

use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Mapping\Import\NameMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\Report\ArrayReport;

class NameMappingConsistency implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var NameMapper $importNameMapper */
    protected $importNameMapper;

    /** @var array */
    protected $products;

    /** @var array */
    protected $models;

    public function __construct(NameMapper $importNameMapper)
    {
        $this->importNameMapper = $importNameMapper;
    }

    /**
     * Check if each models name maps to the same product name.
     */
    public function check()
    {
        $products = $this->getProducts() ?? [];

        /** @var Product $product */
        $topics = [];
        foreach ($products as $product) {
            $issues = $this->getNameMappingIssues($product);
            if (!empty($issues)) {
                $topics[$product->getIcNumber()] = $issues;
            }
        }
        ksort($topics);
        $report = ['pmNameMappingInconsistencies' => $topics];
        $reporter = new ArrayReport();
        $reporter($report);
        return count($topics);
    }

    /**
     * Helper function
     *
     * @param Product $product
     * @return array
     */
    public function getNameMappingIssues(Product $product): array
    {
        $models = $this->getModels();
        if (! $models) return [];

        $variants = $product->getVariants();
        $map = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $number = $variant->getIcNumber();
            $model = $models[$number];
            if (! $models[$number]) {
                $this->log->debug('No model for variant: ' . $number);
                continue;
            }
            $this->importNameMapper->map($model, $product);
            $map[$product->getName()] = $number;
        }
        if (count($map) === 1) {
            return [];
        }
        $issues = [];
        foreach ($map as $name => $number) {
            /** @var Model $model */
            $model = $models[$number];
            $issues[$number] = [
                'imported_name' => $model->getName(),
                'mapped_name'   => $name,
                'options'       => $model->getOptions()
            ];
        }
        return $issues;
    }

    protected function getProducts()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products ?? $this->products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
    }

    protected function getModels()
    {
        return $this->models ?? $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
    }

}