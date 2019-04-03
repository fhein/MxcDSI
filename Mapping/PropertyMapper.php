<?php

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Import\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Import\Flavorist;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use RuntimeException;
use Shopware\Components\Model\ModelManager;

class PropertyMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var array */
    protected $articleMappers;

    /** @var array */
    protected $variantMappers;

    /** @var RegularExpressions $regularExpressions */
    protected $regularExpressions;

    /** @var AssociatedArticlesMapper $associatedArticlesMapper */
    protected $associatedArticlesMapper;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var Reporter $reporter */
    protected $reporter;

    /** @var Flavorist $flavorist */
    protected $flavorist;

    /** @var array */
    protected $config;

    /** @var array */
    protected $mappings;

    /** @var array */
    protected $report;
    protected $articles = null;

    protected $models = null;

    public function __construct(
        ModelManager $modelManager,
        AssociatedArticlesMapper $associatedArticlesMapper,
        RegularExpressions $regularExpressions,
        Flavorist $flavorist,
        Reporter $reporter,
        array $articleMappers,
        array $variantMappers,
        array $config,
        LoggerInterface $log)
    {
        $this->config = $config;
        $this->reporter = $reporter;
        $this->articleMappers = $articleMappers;
        $this->variantMappers = $variantMappers;
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->flavorist = $flavorist;
        $this->associatedArticlesMapper = $associatedArticlesMapper;
        $this->regularExpressions = $regularExpressions;
        $this->reset();
        $this->mappings = [];
        $fn = $this->config['settings']['articleConfigFile'];
        if (file_exists($fn)) {
            /** @noinspection PhpIncludeInspection */
            $this->mappings = include $fn;
        }
    }

    public function reset()
    {
        $this->report = [];
        $this->models = null;
        $this->articles = null;
    }

    public function mapProperties(array $articles)
    {
        if ($this->config['settings']['checkRegularExpressions'] === true) {
            if (! $this->regularExpressions->check()) {
                throw new RuntimeException('Regular expression failure.');
            }
        }
        $this->reset();
        $models = $this->getModels();
        if (! $models || ! $articles) {
            $this->log->debug(__FUNCTION__ . ': no models or no articles found.');
            return;
        }

        /** @var Article $article */
        foreach ($articles as $article) {
            $variants = $article->getVariants();
            $first = true;
            /** @var Variant $variant */
            foreach ($variants as $variant) {
                $model = $models[$variant->getIcNumber()];
                if ($first) {
                    $this->mapModelToArticle($model, $article);
                    $first = false;
                }
                $this->mapModelToVariant($model, $variant);
            }
        }
        $this->associatedArticlesMapper->map($articles);

        ($this->reporter)($this->report, $this->config);
    }

    /**
     * Set all properties of Article maintained by PropertyMapper
     *
     * @param Model $model
     * @param Article $article
     */
    public function mapModelToArticle(Model $model, Article $article)
    {
//        // this will set the linked status to true if a Shopware article with $number exists
//        $article->isLinked();

        foreach ($this->articleMappers as $articleMapper) {
            $articleMapper->map($model, $article);
        }
    }

    /**
     * Set all properties of Variant maintained by PropertyMapper
     *
     * @param Model $model
     * @param Variant $variant
     */
    public function mapModelToVariant(Model $model, Variant $variant)
    {
        foreach ($this->variantMappers as $mapper) {
            $mapper->map($model, $variant);
        }
    }

    public function mapArticleCategory($model, $article) {
        $this->articleMappers['category']->map($model, $article);
    }

    public function mapArticleName($model, $article) {
        $this->articleMappers['name']->map($model, $article);
    }

    public function mapArticleManufacturer($model, $article)
    {
        $this->articleMappers['manufacturer']->map($model, $article);
    }

    public function mapGroupName($name)
    {
        return $this->config['group_names'][$name] ?? $name;
    }

    public function mapOptionName($name)
    {
        $mapping = $this->config['option_names'][$name] ?? $name;
        return str_replace('weiss', 'weiß', $mapping);
    }

    protected function getModels()
    {
        $this->models = $this->models ?? $this->modelManager->getRepository(Model::class)->getAllIndexed();
        return $this->models;
    }
}