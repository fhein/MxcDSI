<?php


namespace MxcDropshipInnocigs\Mapping\Import;


use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ArticleManufacturerMapper
{
    /** @var array $config */
    protected $config;

    /** @var array $report */
    protected $array;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $mappings;

    /** @var array */
    protected $report;

    public function __construct(array $config, LoggerInterface $log)
    {
        $this->config = $config;
        $this->log = $log;
        $this->mappings = [];
        $fn = __DIR__ . '/../../Config/article.config.php';
        if (file_exists($fn)) {
            /** @noinspection PhpIncludeInspection */
            $this->mappings = include $fn;
        }
    }

    public function map(Model $model, Article $article): void
    {
        $this->mapBrand($model, $article);
        $this->mapSupplier($model, $article);
    }

    public function mapSupplier(Model $model, Article $article)
    {
        $supplier = $article->getSupplier();
        if ($supplier === null) {
            $mapping = $this->mappings[$article->getIcNumber()] ?? [];
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

    public function mapBrand(Model $model, Article $article)
    {
        $brand = $article->getBrand();
        if ($brand === null) {
            $mapping = $this->mappings[$article->getIcNumber()] ?? [];
            $manufacturer = $model->getManufacturer();
            $brand = $mapping['brand'] ?? $this->config['manufacturers'][$manufacturer]['brand'] ?? $manufacturer;
            $article->setBrand($brand);
        }
        $this->report['brand'][$article->getBrand()] = true;
    }
}