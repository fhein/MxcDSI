<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Components\Model\ModelManager;

class PropertyMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var Reporter $reporter */
    protected $reporter;

    protected $config;

    protected $report;

    protected $articles = null;
    protected $models = null;

    public function __construct(ModelManager $modelManager, Reporter $reporter, array $config, LoggerInterface $log)
    {
        $this->config = $config;
        $this->reporter = $reporter;
        $this->log = $log;
        $this->init();
        $this->modelManager = $modelManager;
    }

    public function init()
    {
        $this->report = [];
        $this->models = null;
        $this->articles = null;
    }

    /**
     * Set all properties of Variant maintained by PropertyMapper
     *
     * @param Model $model
     * @param Variant $variant
     */
    public function mapModelToVariant(Model $model, Variant $variant)
    {
        $number = $model->getModel();
        $variant->setNumber($this->config['variant_codes'][$number] ?? $number);
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

    /**
     * Set all properties of Article maintained by PropertyMapper
     *
     * @param Model $model
     * @param Article $article
     */
    public function mapModelToArticle(Model $model, Article $article)
    {
        $number = $model->getMaster();
        $article->setNumber($this->config['article_codes'][$number] ?? $number);

        // do not change ordering of the next lines
        $this->mapManufacturer($article, $model->getManufacturer());    // sets supplier, brand and manufacturer
        $this->mapArticleName($model, $article);                        // uses brand, sets name
        $this->mapCategory($article, $model->getCategory());            // uses supplier, brand and name, sets category
    }

    public function mapManufacturer(Article $article, string $manufacturer): void
    {
        $result = $this->config['articles'][$article->getNumber()];
        $article->setBrand($result['brand'] ?? $this->config['manufacturers'][$manufacturer]['brand'] ?? $manufacturer);
        $supplier = $result['supplier'];
        if (!$supplier) {
            if (!in_array($manufacturer, $this->config['innocigs_brands'])) {
                $supplier = $this->config['manufacturers'][$manufacturer]['supplier'] ?? $manufacturer;
            }
        }
        $article->setSupplier($supplier);
        $article->setManufacturer($manufacturer);
        $this->report['brand'][$article->getBrand()] = true;
        $this->report['supplier'][$article->getSupplier()] = true;
    }

    protected function correctSupplierAndBrand(string $name, Article $article)
    {
        $brand = $article->getBrand();
        if (!$brand) {
            return $name;
        }
        $supplier = $article->getSupplier();
        if ($supplier === 'Innocigs') $supplier = 'InnoCigs';
        $isInnocigsBrand = in_array($brand, $this->config['innocigs_brands']);
        $isInnocigsSupplier = ($supplier === 'InnoCigs');

        if ($isInnocigsBrand && $isInnocigsSupplier) {
            // There are some articles from supplier InnoCigs which are not branded
            if (strpos($name, $brand) !== 0 && ! in_array($name, $this->config['articles_without_brand'])) {
                $name = $brand . ' - ' . $name;
            }
            return $name;
        }

        $append = $isInnocigsBrand ? ' - by ' . $brand : '';
        if ($supplier === 'Smoktech') {
            $supplier = 'SMOK';
        }


        if (! $isInnocigsSupplier ) {
            $name = str_replace($brand, $supplier, $name) . $append;
        }
        return $name;
    }

    public function mapArticleName(Model $model, Article $article): void
    {
        $trace['model'] = $model->getModel();
        $trace['imported'] = $model->getName();
        $name = $this->removeOptionsFromArticleName($model);
        $trace['options_removed'] = $name;

        // general name mapping applied first
        $result = $this->config['article_names'][$model->getName()];
        if ($result !== null) {
            $trace['directly_mapped'] = $result;
            $article->setName($result);
            return;
        }

        // rule based name mapping applied next
        $name = $this->correctSupplierAndBrand($name, $article);
        $trace['brand_prepended'] = $name;

        foreach ($this->config['article_name_replacements'] as $replacer => $replacements) {
            $name = $replacer(array_keys($replacements), array_values($replacements), $name);
            $trace[$replacer . '_applied'] = $name;
        }
        $supplier = $article->getSupplier();
        $supplier = $supplier === 'Smoktech' ? 'SMOK' : $supplier;
        $search[] = '~(' . $article->getBrand() . ') ([^\-])~';
        $search[] = '~(' . $supplier . ') ([^\-])~';
        $name = preg_replace($search, '$1 - $2', $name);
        $trace['supplier_separator'] = $name;

        $name = trim($name);

        $article->setName($name);
        $trace['mapped'] = $name;

        $this->report['name'][$trace['imported']] = $trace;
    }

    public function removeOptionsFromArticleName(Model $model)
    {
        // Innocigs variant names include variant descriptions
        // We take the first variant's name and remove the variant descriptions
        // in order to extract the real article name
        $options = explode('##!##', $model->getOptions());
        $name = $model->getName();

        foreach ($options as $option) {
            $option = explode('#!#', $option)[1];

            // '1er Packung' is not a substring of any article name
            if ($option === '1er Packung') {
                continue;
            }

            $number = $model->getModel();

            if (strpos($name, $option) !== false) {
                // article name contains option name
                $before = $name;
                $name = str_replace($option, '', $name);
                $this->report['option'][$number] = [
                    'before' => $before,
                    'after' => $name,
                    'mapped' => true,
                    'option' => $option,
                ];
                continue;
            }

            $name = $this->applyOptionNameMapping($number, $name, $option);

        }
//        if (substr($name, -2) === ' -') {
//            $name = substr($name, 0, strlen($name) - 2);
//        }
        return trim($name);
    }

    protected function applyOptionNameMapping(string $model, string $name, string $option)
    {
        // They introduced some cases where the option name is not equal
        // to the string added to the article name, so we have to check
        // that, also. The implementation here is a hack right now.
        $o = $this->config['article_name_option_fixes'][$option] ?? null;
        $fixApplied = false;
        $fixAvailable = false;
        $before = $name;
        if ($o) {
            $fixAvailable = true;
            if (is_string($o)) {
                $o = [$o];
            }
            foreach ($o as $mappedOption) {
                if (strpos($name, $mappedOption) !== false) {
                    $name = str_replace($mappedOption, '', $name);
                    $fixApplied = true;
                    break;
                }
            }
        }
        $this->report['option'][$model] = [
            'before'       => $before,
            'after'        => $name,
            'option'       => $option,
            'fixAvailable' => $fixAvailable,
            'fixApplied'   => $fixApplied,
        ];
        return $name;
    }

    public function mapCategory(Article $article, ?string $icCategory): void
    {
        $category = null;
        // article configuration has highest priority
        // general category mapping applies next
//        $result = $this->config['articles'][$article->getNumber()]['category'] ?? $this->config['categories'][$icCategory];
//        if ($result !== null) {
//            $article->setCategory($result);
//            $this->categoryMap[$icCategory] = $result;
//            return;
//        }
        foreach ($this->config['categories'] as $key => $settings) {
            if ($key === 'category') {
                $input = $icCategory;
            }
            /** @noinspection PhpUndefinedVariableInspection */
            if (null === $input) {
                $method = 'get' . ucFirst($key);
                if (method_exists($article, $method)) {
                    $input = $article->$method();
                }
            }
            foreach ($settings as $matcher => $mappings) {
                foreach($mappings as $pattern => $mappedCategory) {
                    if (preg_match('~Easy 3~', $article->getName())) {
                        $supplierTag = $article->getBrand();
                    } else {
                        $supplierTag = preg_match('~(Liquid)|(Aromen)|(Basen)|(Shake \& Vape)~',
                            $mappedCategory) === 1 ? $article->getBrand() : $article->getSupplier();
                    }
                    if ($matcher($pattern, $input) === 1) {
                        $category = $this->addSubCategory($mappedCategory, $supplierTag);
                        $category = preg_replace('~(Easy 3 Caps) > (.*)~', '$2 > $1', $category);
                        break 3;
                    }
                }
            }
        }
        if (! $category) {
            $category = '';
        }
        $this->report['category'][$category][$article->getName()] = true;
        $article->setCategory($category);
    }

    protected function addSubCategory(string $name, ?string $subcategory)
    {
        if ($subcategory !== null && $subcategory !== '') {
            $name .= ' > ' . $subcategory;
        }
        return $name;
    }

    public function reapplyPropertyMapping()
    {
        $this->init();
        $models = $this->getModels();
        $articles = $this->getArticles();
        if (! $models || ! $articles) return;

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
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
        $this->modelManager->clear();
        $this->checkArticlePropertyMappingConsistency();
        $this->report();
    }

    /**
     * @param Article $article
     * @return array
     */
    protected function checkMappingConsistency(Article $article): array
    {
        $models = $this->getModels();
        if (! $models) return [];

        $variants = $article->getVariants();
        $mappedArticles = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $mappedArticle = new Article();
            $model = $models[$variant->getIcNumber()];
            $this->mapModelToArticle($model, $mappedArticle);
            $mappedArticles[] = [
                'article' => $mappedArticle,
                'model'   => $model,
            ];
        }
        /** @var Article $mappedArticle */
        $mapped = [];
        foreach ($mappedArticles['article'] as $mappedArticle) {
            foreach (['name', 'brand', 'supplier', 'category', 'number'] as $topic) {
                $getter = 'get' . ucfirst($topic);
                $mapped[$topic][$mappedArticle->$getter()] = true;
            }
        }
        $issues = [];
        foreach (['name', 'brand', 'supplier', 'category', 'number'] as $topic) {
            if (count($mapped[$topic]) !== 1) {
                $issues['topics'][] = $topic;
            }
        }
        if (!empty($issues)) {
            foreach ($issues['topics'] as $topic) {
                $getter = 'get' . ucfirst($topic);
                $mgetter = ($getter === 'getNumber') ? 'getMaster' : $getter;
                foreach ($mappedArticles as $mappedArticle) {
                    $model = $mappedArticle['model'];
                    if (method_exists($model, $mgetter)) {
                        $issues[$topic][$mappedArticle['model']->$mgetter()] = $mappedArticle['article']->$getter();
                    }
                }
            }
        }
        return $issues;
    }

    public function checkArticlePropertyMappingConsistency()
    {
        $this->init();
        $articles = $this->getArticles() ?? [];

        /** @var Article $article */
        $topics = [];
        foreach ($articles as $article) {
            $issue = $this->checkMappingConsistency($article);
            if (! empty($issue)) {
                $topic[$article->getIcNumber()] = $issue;
            }
        }
        ksort($topics);
        $topics = [ 'pmPropertyMappingInconsistencies' => $topics ];
        $report = new ArrayReport();
        $report($topics);
    }

    protected function getArticles()
    {
        $this->articles = $this->articles ?? $this->modelManager->getRepository(Article::class)->getAllIndexed();
        return $this->articles;
    }

    protected function getModels()
    {
        $this->models = $this->models ?? $this->modelManager->getRepository(Model::class)->getAllIndexed();
        return $this->models;
    }

    public function report() {
        ($this->reporter)($this->report, $this->config);
    }

}