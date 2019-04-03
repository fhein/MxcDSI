<?php


namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ArticleManufacturerMapper extends BaseImportMapper implements ArticleMapperInterface
{
    /** @var array */
    protected $report;

    public function map(Model $model, Article $article): void
    {
        $this->mapBrand($model, $article);
        $this->mapSupplier($model, $article);
    }

    protected function mapSupplier(Model $model, Article $article)
    {
        $supplier = $article->getSupplier();
        if ($supplier === null) {
            $mapping = $this->config['articles'][$article->getIcNumber()] ?? [];
            $manufacturer = $model->getManufacturer();
            $supplier = $mapping['supplier'];
            if (!$supplier) {
                if (!in_array($manufacturer, $this->config['innocigs_brands'])) {
                    $supplier = $this->config['manufacturers'][$manufacturer]['supplier'] ?? $manufacturer;
                }
            }
            $article->setSupplier($supplier);
        }
        $this->report['supplier'][$article->getSupplier()] = true;
    }

    protected function mapBrand(Model $model, Article $article)
    {
        $brand = $article->getBrand();
        if ($brand === null) {
            $mapping = $this->config['articles'][$article->getIcNumber()] ?? [];
            $manufacturer = $model->getManufacturer();
            $brand = $mapping['brand'] ?? $this->config['manufacturers'][$manufacturer]['brand'] ?? $manufacturer;
            $article->setBrand($brand);
        }
        $this->report['brand'][$article->getBrand()] = true;
    }
}