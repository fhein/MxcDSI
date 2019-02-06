<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayMap;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Report\Mapper\SuccessiveReplacer;
use Shopware\Components\Model\ModelManager;

class PropertyMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    protected $config;

    protected $mismatchedOptionNames;
    protected $nameTrace;
    protected $categoryUsage;
    protected $brands;
    protected $suppliers;

    protected $articles = null;
    protected $models = null;

    public function __construct(ModelManager $modelManager, array $config, LoggerInterface $log)
    {
        $this->config = $config;
        $this->log = $log;
        $this->init();
        $this->modelManager = $modelManager;
    }

    public function init()
    {
        $this->nameTrace = [];
        $this->mismatchedOptionNames = [];
        $this->categoryUsage = [];
        $this->brands = [];
        $this->suppliers = [];
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
        $this->brands[$article->getBrand()] = true;
        $this->suppliers[$article->getSupplier()] = true;
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
        $brand = $article->getBrand();
        if ($brand && in_array($brand, $this->config['innocigs_brands']) && (strpos($name, $brand) !== 0)) {
            $name = $brand . ' - ' . $name;
        }
        $trace['brand_prepended'] = $name;

        foreach ($this->config['article_name_replacements'] as $replacer => $replacements) {
            $name = $replacer(array_keys($replacements), array_values($replacements), $name);
            $trace[$replacer . '_applied'] = $name;
        }

        $name = trim($name);

        $article->setName($name);
        $trace['mapped'] = $name;

        $this->nameTrace[$trace['imported']] = $trace;
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

            if (strpos($name, $option) !== false) {
                // article name contains option name
                $name = str_replace($option, '', $name);
                continue;
            }

            $name = $this->applyOptionNameMapping($model->getModel(), $name, $option);
        }
        if (substr($name, -2) === ' -') {
            $name = substr($name, 0, strlen($name) - 2);
        }
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
        $this->mismatchedOptionNames[$model] = [
            'name'         => $name,
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
                        $supplierTag = preg_match('~(Liquid)|(Aromen)|(Basen)~',
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
        $this->categoryUsage[$category][$article->getName()] = true;
        $article->setCategory($category);
    }

    protected function addSubCategory(string $name, ?string $subcategory)
    {
        if ($subcategory !== null && $subcategory !== '') {
            $name .= ' > ' . $subcategory;
        }
        return $name;
    }

    public function logMappingResults()
    {
        ksort($this->brands);
        ksort($this->suppliers);
        ksort($this->nameTrace);
        ksort($this->mismatchedOptionNames);
        ksort($this->categoryUsage);
        foreach ($this->categoryUsage as &$array) {
            ksort($array);
        }

        $unchangedArticleNames = array_map(function ($value) {
            return ($value['imported'] === $value['mapped']);
        }, $this->nameTrace);
        $unchangedArticleNames = array_keys(array_filter(
            $unchangedArticleNames,
            function ($value) {
                return $value === true;
            }
        ));

        $namesWithoutRemovedOptions = array_map(function ($value) {
            return ($value['imported'] === $value['options_removed']);
        }, $this->nameTrace);
        $namesWithoutRemovedOptions = array_keys(array_filter($namesWithoutRemovedOptions, function ($value) {
            return $value === true;
        }));

        $optionMappingIssues = array_filter($this->mismatchedOptionNames, function ($value) {
            false === $value['fixAvailable'] || false === $value['fixApplied'];
        });

        $nameMap = array_values(array_map(function ($value) {
            return [
                'imported' => $value['imported'],
                'mapped  ' => $value['mapped'],
            ];
        }, $this->nameTrace));

        $pregReplace = [];
        foreach ($this->nameTrace as $key => $entry) {
            $pregReplace[$key] = $entry['brand_prepended'];
        }
        $mapper = new ArrayMap();
        $pregReplace = $mapper($pregReplace,
            [
                SuccessiveReplacer::class => [
                    'replacer'     => 'preg_replace',
                    'replacements' => $this->config['article_name_replacements']['preg_replace'],
                ],
            ]
        );

        $strReplace = [];
        foreach ($this->nameTrace as $key => $entry) {
            $strReplace[$key] = $entry['preg_replace_applied'];
        }
        $strReplace = $mapper($strReplace,
            [
                SuccessiveReplacer::class => [
                    'replacer'     => 'str_replace',
                    'replacements' => $this->config['article_name_replacements']['str_replace'],
                ],
            ]
        );

        $topics = [
            'mismatchedOptionNames'     => $optionMappingIssues,
            'namesWithoutRemovedOption' => $namesWithoutRemovedOptions,
            'brands'                    => array_keys($this->brands),
            'suppliers'                 => array_keys($this->suppliers),
            'articleNameMap'            => $nameMap,
            'articleNameTrace'          => $this->nameTrace,
            'articleNames'              => array_column($nameMap, 'mapped  '),
            'articleNamesUnmapped'      => $unchangedArticleNames,
            'articleNamesPregReplace'   => $pregReplace,
            'articleNamesStrReplace'    => $strReplace,
            'categoryUsage'             => $this->categoryUsage,
        ];

        $report = new ArrayReport();
        $report($topics);
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
        $this->logMappingResults();
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
        $topics = [ 'propertyMappingInconsistencies' => $topics ];
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
}