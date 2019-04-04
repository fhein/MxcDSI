<?php

namespace MxcDropshipInnocigs\Mapping\Csv;

use Doctrine\Common\Collections\Collection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Csv\CsvTool;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Price;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ArticlePrices
{
    protected $articlePricesFile = __DIR__ . '/../../Config/article.prices.xlsx';

    /** @var ArticleMapper $articleMapper */
    protected $articleMapper;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $indexMap;

    /** @var array */
    protected $models;

    /** @var array */
    protected $articles;

    public function __construct(ModelManager $modelManager, ArticleMapper $articleMapper, LoggerInterface $log)
    {
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->articleMapper = $articleMapper;
    }

    public function import()
    {
        $records = (new CsvTool())->import($this->articlePricesFile);
        if (! is_array($records) || empty($records)) return;


        $this->log->debug(var_export($records[0], true));
        $keys = array_keys($records[0]);
        $this->indexMap = [];
        foreach ($keys as $key) {
            if (strpos($key, 'VK brutto') === 0) {
                $customerGroupKey = explode(' ', $key)[2];
                $this->indexMap[$key] = $customerGroupKey;
            }
        }

        foreach ($records as $record) {
            $this->updatePrices($record);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    public function export()
    {

        $articles = $this->getArticles();
        $data = [];
        $headers = null;
        foreach ($articles as $article) {
            $data[] = $this->getArticleInfo($article);
        }
        $headers[] = array_keys($data[0]);
        usort($data,
            function($one, $two) {
                $t1 = $one['type'];
                $t2 = $two['type'];
                if ($t1 > $t2) {
                    return true;
                }
                if ($t1 === $t2) {
                    $s1 = $one['supplier'];
                    $s2 = $two['supplier'];
                    if ($s1 > $s2) {
                        return true;
                    }
                    if ($s1 === $s2) {
                        return $one['brand'] > $two['brand'];
                    }
                }
                return false;
            }
        );
        $data = array_merge($headers, $data);

        $this->createExcelSheet($data);
//        $csv = new CsvTool();
//        $csv->export($this->articlePricesCsv, $data);
    }

    protected function updatePrices(array $record)
    {
        /** @var Article $article */
        $article = $this->getArticles()[$record['icNumber']];
        if (! $article) return;

        $prices = [];

        foreach ($this->indexMap as $column => $customerGroup) {
            $price = $record[$column] === '' ? $record['UVP brutto'] : $record[$column];
            $prices[] = $customerGroup . MXC_DELIMITER_L1 . $price;
        }
        $prices = implode(MXC_DELIMITER_L2, $prices);

        $variants = $article->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            if (! $this->isSinglePack($variant)) continue;
            $variant->setRetailPrices($prices);
            $this->articleMapper->setRetailPrices($variant);
        }
    }

    protected function getArticles()
    {
        if (!$this->articles) {
            $this->articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        }
        return $this->articles;
    }

    protected function getModels()
    {
        if (!$this->models) {
            $this->models = $this->modelManager->getRepository(Model::class)->getAllIndexed();
        }
        return $this->models;
    }

    protected function getArticleInfo(Article $article)
    {
        $info['icNumber'] = $article->getIcNumber();
        $info['type'] = $article->getType();
        $info['supplier'] = $article->getSupplier();
        $info['brand'] = $article->getBrand();
        $info['name'] = $article->getName();
        list($info['EK netto'], $info['UVP brutto']) = $this->getPrices($article->getVariants());
        $customerGroupKeys = array_keys($this->articleMapper->getCustomerGroups());
        $shopwarePrices = $this->getCurrentPrices($article);
        foreach ($customerGroupKeys as $key) {
            $info['VK brutto ' . $key] = $shopwarePrices[$key] ?? '';
        }
        return $info;
    }

    protected function isSinglePack(Variant $variant)
    {
        $model = $this->getModels()[$variant->getIcNumber()];
        if (strpos($model->getOptions(), '1er Packung') !== false) {
            return true;
        }
        return false;
    }

    protected function getCurrentPrices(Article $article)
    {
        $variants = $article->getVariants();
        /** @var Variant $variant */
        $detailPrices = [];
        foreach ($variants as $variant) {
            if (! $this->isSinglePack($variant)) continue;
            $detail = $variant->getDetail();
            if (! $detail) continue;
            $shopwarePrices = $detail->getPrices();
            /** @var Price $price */
            foreach ($shopwarePrices as $price) {
                $detailPrices[$price->getCustomerGroup()->getKey()][$variant->getIcNumber()] = $price->getPrice();
            }
        }
        $prices = [];
        foreach ($detailPrices as $key => $price) {
            // $prices[$key] = str_replace ('.', ',', strval(max($price) * 1.19));
            $prices[$key] = max($price) * 1.19;
        }

        return $prices;
    }


    protected function getPrices(Collection $variants)
    {
        $purchasePrice = 0.0;
        $retailPrice = 0.0;
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            /** @var Model $model */
            if (! $this->isSinglePack($variant)) continue;
            $price = $variant->getPurchasePrice();
            if ($price > $purchasePrice) {
//                $purchasePrice = str_replace('.', ',', strval($price));
                  $purchasePrice = $price;
            }
            $price = $variant->getRecommendedRetailPrice();
            if ($price > $retailPrice) {
//                $retailPrice = str_replace('.', ',', strval($price));
                $retailPrice = $price;
            }
        }
        return [$purchasePrice, $retailPrice];
    }

    /**
     * @param array $data
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function createExcelSheet(array $data): void
    {
        $spreadSheet = new Spreadsheet();
        $sheet = $spreadSheet->getActiveSheet();
        $sheet->fromArray($data);
        $sheet->setTitle('Preise');
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('E')->setWidth(80);
        foreach (range('F', 'K') as $col) {
            $sheet->getColumnDimension($col)->setWidth(16);

        }
        $sheet->freezePane('A2');
        $highest = $sheet->getHighestRowAndColumn();

        $sheet->getStyle('F2:'. $highest['column'] . $highest['row'])->getNumberFormat()->setFormatCode('0.00');
        $writer = new Xlsx($spreadSheet);
        $writer->save($this->articlePricesFile);
    }
}