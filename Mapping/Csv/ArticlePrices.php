<?php

namespace MxcDropshipInnocigs\Mapping\Csv;

use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Csv\CsvTool;
use Shopware\Components\Model\ModelManager;

class ArticlePrices
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    protected $models;

    public function __construct(ModelManager $modelManager, LoggerInterface $log)
    {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

    public function import()
    {
    }

    public function export()
    {
        $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
        $articles = $this->modelManager->getRepository(Article::class)->findAll();
        $data = [];
        $headers = null;
        foreach ($articles as $article) {
            $data[] = $this->getArticleInfo($article);
        }
        usort($data, function($one, $two) { return $one['type'] > $two['type'];});
        $csv = new CsvTool();
        $csv->export(__DIR__ . '/../../Config/prices.config.csv', $data);
    }

    protected function getArticleInfo(Article $article)
    {
        $info['type'] = $article->getType();
        $info['icNumber'] = $article->getIcNumber();
        $info['name']     = $article->getName();
        list($info['netto EK'], $info['brutto UVP']) = $this->getPrices($article->getVariants());
        $info['retail_EK'] = '';
        $info['retail_FR'] = '';
        $info['retail_MA'] = '';
        return $info;
    }

    protected function getPrices(Collection $variants)
    {
        $purchasePrice = 0.0;
        $retailPrice = 0.0;
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            /** @var Model $model */
            $model = $this->models[$variant->getIcNumber()];
            if (strpos($model->getOptions(), '1er Packung') === false) continue;
            $price = $variant->getPurchasePrice();
            if ($price > $purchasePrice) {
                $purchasePrice = str_replace('.', ',', strval($price));
            }
            $price = $variant->getRetailPrice();
            if ($price > $retailPrice) {
                $retailPrice = str_replace('.', ',', strval($price));
            }
        }
        return [$purchasePrice, $retailPrice];
    }
}