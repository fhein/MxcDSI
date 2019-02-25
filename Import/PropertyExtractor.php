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
        $properties = $article->getProperties() ?? new ArticleProperties();
        $this->modelManager->persist($properties);
        $article->setProperties($properties);
        $properties->setIcNumber($article->getIcNumber());
        $type = $this->extractType($article);
        $properties->setType($type);
        $properties->setName($article->getName());
        $properties->setAssociatedProduct($this->extractAssociatedProduct($article));
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

    protected function extractAssociatedProduct(Article $article)
    {
        $name = $article->getName();
        $matches = [];

        if (preg_match('~^(.* -) (.*) (- .*)~', $name, $matches) === 1) {
            $this->log->debug('Product: ' . $name . ', extracted: ' . $matches[2]);
            $this->log->debug('Matches: ' . var_export($matches, true));
            return $matches[2];
        };
        return '';
    }

}