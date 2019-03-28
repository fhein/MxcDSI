<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\ArticleMapping;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Toolbox\Regex\RegexChecker;
use RuntimeException;
use Shopware\Components\Model\ModelManager;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class PropertyMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var PropertyDerivator $propertyDerivator */
    protected $propertyDerivator;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var Reporter $reporter */
    protected $reporter;

    /** @var Flavorist $flavorist */
    protected $flavorist;

    /** @var RegexChecker $regexChecker */
    protected $regexChecker;

    /** @var array */
    protected $mappedProperties;

    /** @var array */
    protected $config;

    /** @var array */
    protected $report;

    protected $articles = null;
    protected $models = null;

    public function __construct(
        ModelManager $modelManager,
        PropertyDerivator $propertyDerivator,
        Flavorist $flavorist,
        Reporter $reporter,
        array $config,
        LoggerInterface $log)
    {
        $this->config = $config;
        $this->reporter = $reporter;
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->flavorist = $flavorist;
        $this->propertyDerivator = $propertyDerivator;
        $this->regexChecker = new RegexChecker();
        $this->reset();
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
            if (!$this->checkRegularExpressions()) {
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
        $this->storeArticleMappings($articles);
        $this->propertyDerivator->derive($articles);
        $this->propertyDerivator->export();
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
        $number = $model->getMaster();
        $article->setNumber($this->config['article_codes'][$number] ?? $number);

        // do not change ordering of the next lines
        $this->mapManufacturer($article, $model->getManufacturer());    // sets supplier, brand and manufacturer
        $article->setName($this->mapArticleName($model, $article));     // uses brand, sets name
        $this->deriveArticleType($article);                             // uses name, sets type,
        $this->mapCategory($model, $article);                           // uses supplier, brand and name, sets category
        $this->mapFlavor($article);
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

    public function mapManufacturer(Article $article, string $manufacturer): void
    {
        $mapping = $article->getMapping();
        $article->setBrand($mapping->getBrand() ?? $this->config['manufacturers'][$manufacturer]['brand'] ?? $manufacturer);
        $supplier = $mapping->getSupplier();
        if (!$supplier) {
            if (! in_array($manufacturer, $this->config['innocigs_brands'])) {
                $supplier = $this->config['manufacturers'][$manufacturer]['supplier'] ?? $manufacturer;
            }
        }
        $article->setSupplier($supplier);
        $article->setManufacturer($manufacturer);
        $this->report['brand'][$article->getBrand()] = true;
        $this->report['supplier'][$article->getSupplier()] = true;
    }

    protected function applySupplierAndBrandDeocoration(string $name, Article $article)
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

    public function mapArticleName(Model $model, Article $article): string
    {
        $modelName = $model->getName();
        $this->report['name'][$modelName]['model'] = $model->getModel();
        $trace['imported'] = $model->getName();
        $name = $this->replace($modelName, 'name_prepare');
        $trace['name_prepared'] = $name;
        $name = $this->removeOptionsFromModelName($name, $model);
        $trace['options_removed'] = $name;

        // general name mapping applied first
        $result = $this->config['article_names'][$model->getName()];
        if ($result !== null) {
            $trace['directly_mapped'] = $result;
            return $result;
        }

        // rule based name mapping applied next
        $name = $this->applySupplierAndBrandDeocoration($name, $article);
        $trace['brand_prepended'] = $name;

        $name = $this->replace($name, 'article_name_replacements');
        $trace['after_name_replacements'] = $name;

        $supplier = $article->getSupplier();
        $supplier = $supplier === 'Smoktech' ? 'SMOK' : $supplier;

        $search[] = '~(' . $article->getBrand() . ') ([^\-])~';
        $search[] = '~(' . $supplier . ') ([^\-])~';
        $name = preg_replace($search, '$1 - $2', $name);
        $trace['supplier_separator'] = $name;
        $search = $this->config['product_names'][$article->getBrand()];
        if (null !== $search) {
            $name = preg_replace($search, '$1 -', $name);
            $trace['product_separator'] = $name;
        }

        $name = $this->replace($name, 'name_cleanup');
        $name = preg_replace('~\s+~', ' ', $name);

        $trace['mapped'] = $name;
        $this->report['name'][$trace['imported']] = $trace;
        return $name;
    }

    protected function deriveArticleType(Article $article)
    {
        $name = $article->getName();
        $types = $this->config['name_type_mapping'];
        foreach ($types as $pattern => $type) {
            if (preg_match($pattern, $name) === 1) {
                $article->setType($this->config['types'][$type]);
                return;
            }
        }
//        $article->setType($this->config['types'][self::TYPE_UNKNOWN]);
        $article->setType('');
    }

    protected function mapFlavor(Article $article) {
        $flavor = $this->config['flavors'][$article->getIcNumber()]['flavor'];
        if (is_array($flavor) && ! empty($flavor)) {
            $article->setFlavor(implode(', ', $flavor));
        }
    }

    public function removeOptionsFromModelName(string $name, Model $model)
    {
        // Innocigs variant names include variant descriptions
        // We take the first variant's name and remove the variant descriptions
        // in order to derive the real article name
        $options = explode(MXC_DELIMITER_L2, $model->getOptions());
//        if (strpos($name, 'Steam Crave Aromamizer Plus Bubble Glastank') !== false)
//            xdebug_break();

        foreach ($options as $option) {
            $option = explode(MXC_DELIMITER_L1, $option)[1];
            $number = $model->getModel();

            if (strpos($name, $option) !== false) {
                // article name contains option name
                $before = $name;
                $replacement = $this->config['option_replacements'][$option] ?? '';
                $name = str_replace($option, $replacement, $name);
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
        $name = preg_replace('~\s+~', ' ', $name);
        return trim($name);
    }

    protected function applyOptionNameMapping(string $model, string $name, string $option)
    {
        // They introduced some cases where the option name is not equal
        // to the string added to the article name, so we have to check
        // that also.
        $o = $this->config['article_name_option_fixes'][$option] ?? null;
        $fixApplied = false;
        $fixAvailable = $o !== null;
        $before = $name;
        if ($fixAvailable && $o !== '') {
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

    public function mapCategory(Model $model, Article $article): void
    {
        $category = null;

        foreach ($this->config['categories'] as $key => $settings) {
            if ($key === 'category') {
                $input = $model->getCategory();
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
                        $category = preg_replace('~(Easy 3( Caps)?) > (.*)~', '$3 > $1', $category);
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

    /**
     * @param Article $article
     * @return array
     */
    protected function getNameMappingIssues(Article $article): array
    {
        $models = $this->getModels();
        if (! $models) return [];

        $variants = $article->getVariants();
        $map = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $number = $variant->getIcNumber();
            $model = $models[$number];
            $map[$this->mapArticleName($model, $article)] = $number;
        }
        if (count($map) === 1) return [];
        $issues = [];
        foreach ($map as $name => $number) {
            /** @var Model $model */
            $model = $models[$number];
            $issues[$number] = [
                'imported_name' => $model->getName(),
                'mapped_name' => $name,
                'options' => $model->getOptions()
            ];
        }
        return $issues;
    }

    public function checkNameMappingConsistency()
    {
        $articles = $this->getArticles() ?? [];

        /** @var Article $article */
        $topics = [];
        foreach ($articles as $article) {
            $issues = $this->getNameMappingIssues($article);
            if (! empty($issues)) {
                $topics[$article->getIcNumber()] = $issues;
            }
        }
        ksort($topics);
        $report = [ 'pmNameMappingInconsistencies' => $topics ];
        $reporter = new ArrayReport();
        $reporter($report);
        return count($topics);
    }

    public function checkRegularExpressions()
    {
        $errors = [];
        foreach ($this->config['categories'] as $entry) {
            $entries = $entry['preg_match'];
            if (! is_array($entries)) continue;
            if (false === $this->regexChecker->validate(array_keys($entry['preg_match']))) {
                $errors = array_merge($errors, $this->regexChecker->getErrors());
            }
        }
        foreach (['name_prepare', 'name_cleanup', 'article_name_replacements'] as $entry) {
            $entries = $this->config[$entry]['preg_replace'];
            if (! is_array($entries)) continue;
            if (false === $this->regexChecker->validate(array_keys($entries))) {
                $errors = array_merge($errors, $this->regexChecker->getErrors());
            }
        }
        foreach ($this->config['product_names'] as $entry) {
            if (! is_array($entry)) continue;
            if (false === $this->regexChecker->validate($entry)) {
                $errors = array_merge($errors, $this->regexChecker->getErrors());
            }
        }

        if (false === $this->regexChecker->validate(array_keys($this->config['name_type_mapping']))) {
            $errors = array_merge($errors, $this->regexChecker->getErrors());
        }

        $result = empty($errors);
        if (false === $result) {
            foreach ($errors as $error) {
                $this->log->err('Invalid regular expression: \'' . $error . '\'');
            }
        }
        return $result;
    }

    protected function replace(string $topic, string $what) {
        $config = $this->config[$what];
        if (null === $config) return $topic;
        foreach ($config as $replacer => $replacements) {
            $search = array_keys($replacements);
            $replace = array_values($replacements);
            $topic = $replacer($search, $replace, $topic);
        }
        return $topic;
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

    protected function getMappedProperties()
    {
        if (! $this->mappedProperties) {
            $mapping = new ArticleMapping();
            $properties = $mapping->getMappedPropertyNames();
            $mappedProperties = [];
            foreach ($properties as $property) {
                $mappedProperties[$property] = 'get' . ucfirst($property);
            }
            $this->mappedProperties = $mappedProperties;
        }
        return $this->mappedProperties;
    }

    public function storeArticleMapping(Article $article)
    {
        $settings = [];
        $mappedProperties = $this->getMappedProperties();
        foreach ($mappedProperties as $property => $getProperty) {
            $settings[$property] = $article->$getProperty();
        }
        $article->getMapping()->fromArray($settings);
    }

    protected function storeArticleMappings(array $articles) {
        foreach ($articles as $article) {
            $this->storeArticleMapping($article);
        }
    }

    const TYPE_UNKNOWN              = 0;
    const TYPE_E_CIGARETTE          = 1;
    const TYPE_BOX_MOD              = 2;
    const TYPE_E_PIPE               = 3;
    const TYPE_LIQUID               = 4;
    const TYPE_AROMA                = 5;
    const TYPE_SHAKE_VAPE           = 6;
    const TYPE_HEAD                 = 7;
    const TYPE_TANK                 = 8;
    const TYPE_SEAL                 = 9;
    const TYPE_DRIP_TIP             = 10;
    const TYPE_POD                  = 11;
    const TYPE_CARTRIDGE            = 12;
    const TYPE_CELL                 = 13;
    const TYPE_CELL_BOX             = 14;
    const TYPE_BASE                 = 15;
    const TYPE_CHARGER              = 16;
    const TYPE_BAG                  = 17;
    const TYPE_TOOL                 = 18;
    const TYPE_WADDING              = 19; // Watte
    const TYPE_WIRE                 = 20;
    const TYPE_BOTTLE               = 21;
    const TYPE_SQUONKER_BOTTLE      = 22;
    const TYPE_VAPORIZER            = 23;
    const TYPE_SHOT                 = 24;
    const TYPE_CABLE                = 25;
    const TYPE_BOX_MOD_CELL         = 26;
    const TYPE_COIL                 = 27;
    const TYPE_RDA_BASE             = 28;
    const TYPE_MAGNET               = 29;
    const TYPE_MAGNET_ADAPTOR       = 30;
    const TYPE_ACCESSORY            = 31;
    const TYPE_BATTERY_CAP          = 32;
    const TYPE_EXTENSION_KIT        = 33;
    const TYPE_CONVERSION_KIT       = 34;
    const TYPE_CLEAROMIZER          = 35;
    const TYPE_CLEAROMIZER_RTA      = 36;
    const TYPE_CLEAROMIZER_RDTA     = 37;
    const TYPE_CLEAROMIZER_RDSA     = 38;
    const TYPE_E_HOOKAH             = 39;
    const TYPE_SQUONKER_BOX         = 40;
    const TYPE_EMPTY_BOTTLE         = 41;
    const TYPE_EASY3_CAP            = 42;
    const TYPE_DECK                 = 43;
    const TYPE_TOOL_HEATING_PLATE   = 44;
    const TYPE_HEATING_PLATE        = 45;
    const TYPE_DRIP_TIP_CAP         = 46;
    const TYPE_TANK_PROTECTION      = 47;
    const TYPE_STORAGE              = 48;

    const TYPE_BATTERY_SLEEVE       = 49;
}