<?php


namespace MxcDropshipInnocigs\Mapping\Check;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Import\NameMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Components\Model\ModelManager;

class NameMappingConsistency
{
    /** @var NameMapper $importNameMapper */
    protected $importNameMapper;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $products;

    /** @var array */
    protected $Models;

    public function __construct(ModelManager $modelManager, NameMapper $importNameMapper, LoggerInterface $log)
    {
        $this->importNameMapper = $importNameMapper;
        $this->modelManager = $modelManager;
        $this->log = $log;
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
        if (!$models) {
            return [];
        }

        $variants = $product->getVariants();
        $map = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $number = $variant->getIcNumber();
            $model = $models[$number];
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
        return $this->Models ?? $this->Models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
    }

}