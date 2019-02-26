<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 25.02.2019
 * Time: 14:24
 */

namespace MxcDropshipInnocigs\Import;


use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\ArticleProperties;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;

class PropertyExtractor
{
    protected $log;
    protected $modelManager;
    protected $config;

    public function __construct(ModelManager $modelManager, Config $config, LoggerInterface $log)
    {
        $this->log = $log;
        $this->config = $config->toArray();
        $this->modelManager = $modelManager;
    }

    public function extract()
    {
        $articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        foreach ($articles as $number => $article) {
            $this->extractArticleProperties($article);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
        $this->extractProductNames();
    }

    protected function extractProductNames() {
        $articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        $products = [];
        foreach ($articles as $number => $article) {
            /** @var Article $article */
            $name = $article->getName();
            $comp = explode(' - ', $name);
            $product = preg_replace('~ \(\d+ Stück pro Packung\)~', '', $comp[1]);
            $products[$product] = true;
        }
        ksort($products);
        (new ArrayReport())(['peProducts' => array_keys($products)]);
    }

    public function extractArticleProperties(Article $article)
    {
        $article->setType($this->extractType($article));
        $article->setCommonName($this->extractCommonName($article));
        $article->setPiecesPerPack($this->extractPiecesPerPack($article));
        $article->setDosage($this->extractDosage($article));
    }

    protected function extractType(Article $article)
    {
        $category = $article->getCategory();
        foreach ($this->config['category_type_map'] as $cat => $type) {
            if (strpos($category, $cat) === 0) {
                return $type;
            }
        }
        $type = $this->config['category_type_map'][$category];
        if ($type !== null) {
            return $type;
        }
        $name = $article->getName();
        foreach ($this->config['name_type_map'] as $pattern => $type) {
            if (preg_match($pattern, $name) === 1) {
                return $type;
            }
        }
        return ArticleProperties::TYPE_UNKNOWN;
    }

    protected function extractCommonName(Article $article)
    {
        $name = $article->getName();
        $raw = explode(' - ', $name);
        $index = $this->config['name_index'][$raw[0]][$raw[1]] ?? 1;
        return $raw[$index] ?? $raw[0];
    }

    protected function extractPiecesPerPack(Article $article)
    {
        $name = $article->getName();
        $matches = [];
        $ppp = 1;
        if (preg_match('~\((\d+) Stück~', $name, $matches) === 1) {
            $ppp = $matches[1];
        };
        return $ppp;
    }

    protected function extractDosage(Article $article)
    {
        $supplier = $article->getSupplier();
        $dosage = $this->config['recommended_dosage'][$supplier];
        return $dosage;
    }

    public function export() {
        $articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        $export = [];
        /** @var  Article $article */
        foreach($articles as $number => $article) {
            $pg = $article->getPg();
            if ($pg !== null) {
                $vg = $article->getVg();
                $export[$number]['base'] = [
                    'vg' => $vg,
                    'pg' => $pg,
                ];
            }
            $dosage = $article->getDosage();
            if ($dosage['min'] !== null) {
                $export[$number]['dosage'] = $dosage;
            }
        }
        (new ArrayReport())(['peProperties' => $export]);
    }
}