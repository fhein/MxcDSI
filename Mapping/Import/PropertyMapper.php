<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use RuntimeException;
use Shopware\Components\Model\ModelManager;

class PropertyMapper
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ArticleNameMapper $articleNameMapper */
    protected $articleNameMapper;

    /** @var ArticleManufacturerMapper $articleManufacturerMapper */
    protected $articleManufacturerMapper;

    /** @var ArticleTypeMapper $articleTypeMapper */
    protected $articleTypeMapper;

    /** @var RegularExpressions $regularExpressions */
    protected $regularExpressions;

    /** @var PropertyDerivator $propertyDerivator */
    protected $propertyDerivator;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var Reporter $reporter */
    protected $reporter;

    /** @var Flavorist $flavorist */
    protected $flavorist;

    /** @var array */
    protected $mappedProperties;

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
        ArticleNameMapper $articleNameMapper,
        ArticleTypeMapper $articleTypeMapper,
        ArticleManufacturerMapper $articleManufacturerMapper,
        PropertyDerivator $propertyDerivator,
        RegularExpressions $regularExpressions,
        Flavorist $flavorist,
        Reporter $reporter,
        array $config,
        LoggerInterface $log)
    {
        $this->config = $config;
        $this->reporter = $reporter;
        $this->articleNameMapper = $articleNameMapper;
        $this->articleTypeMapper = $articleTypeMapper;
        $this->articleManufacturerMapper = $articleManufacturerMapper;
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->flavorist = $flavorist;
        $this->propertyDerivator = $propertyDerivator;
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

        // this will set the linked status to true if a Shopware article with $number exists
        $article->isLinked();

        // do not change ordering of the next lines
        $this->articleManufacturerMapper->map($model, $article);            // sets supplier, brand and manufacturer
        $article->setName($this->articleNameMapper->map($model, $article)); // uses brand, sets name
        $this->derivePiecesPerPack($article);                               // uses name, sets piecesPerPack
        $this->deriveCommonName($article);                                  // uses name, sets commonName
        $this->articleTypeMapper->map($article);                            // uses name, sets type
        $this->deriveAromaDosageRecommendation($article);                   // uses type, sets dosage
        $this->mapCategory($model, $article);                               // uses supplier, brand and name, sets category
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

    /**
     * The common name of an article is the pure product name without
     * supplier, article group and without any other info.
     *
     * The common name gets determined here and is utilized to identify
     * related products.
     *
     * @param Article $article
     */
    protected function deriveCommonName(Article $article)
    {
        $name = $article->getName();
        $raw = explode(' - ', $name);
        $index = $this->config['common_name_index'][$raw[0]][$raw[1]] ?? 1;
        $name = trim($raw[$index] ?? $raw[0]);
        $replacements = [ '~ \(\d+ Stück pro Packung\)~', '~Head$~'];
        $name = preg_replace($replacements, '', $name);
        $article->setCommonName(trim($name));
    }

    /**
     * If a product in general contains several pieces, i.e. not as an option,
     * the mapped product name contains a substring like (xx Stück pro Packung).
     *
     * This xx number of pieces gets derived here.
     *
     * @param Article $article
     */
    protected function derivePiecesPerPack(Article $article)
    {
        $name = $article->getName();
        $matches = [];
        $ppp = 1;
        if (preg_match('~\((\d+) Stück~', $name, $matches) === 1) {
            $ppp = $matches[1];
        };
        $article->setPiecesPerPack($ppp);
    }

    /**
     * Aromas typically come with a dosage recommendation by the supplier.
     * This recommendation is provided manually via our article configuration.
     *
     * In some cases InnoCigs article descriptions mention the dosage recommendation
     * in the text.
     *
     * This function checks if a manual configuration is available and otherwise
     * tries to extract the dosage recommendation from the article's description.
     *
     * @param Article $article
     */
    protected function deriveAromaDosageRecommendation(Article $article)
    {
        if ($article->getType() !== 'AROMA') return;
        $icNumber = $article->getIcNumber();
        $dosage = $this->mappings[$icNumber]['dosage'];
        if ($dosage !== null) {
            $article->setDosage($dosage);
            return;
        }

        $description = preg_replace('~\n~', '', $article->getDescription());
        $search = '~.*Dosierung[^\d]*(\d+).*(-|(bis)) *(\d+).*~';
        $replace = '$1-$4';
        $dosage = preg_replace($search, $replace, $description);

        if ($dosage === $description) return;

        $article->setDosage($dosage);
        $this->mappings[$icNumber]['dosage'] = $dosage;
    }

    /**
     * Assign the product flavor from article configuration.
     *
     * @param Article $article
     */
    protected function mapFlavor(Article $article) {
        $flavor = $article->getFlavor();
        if ($flavor !== null) return;

        $flavor = $this->config['flavors'][$article->getIcNumber()]['flavor'];
        if (is_array($flavor) && ! empty($flavor)) {
            $article->setFlavor(implode(', ', $flavor));
            $this->mappings[$article->getIcNumber()]['flavor'] = $flavor;
        }
    }

    /**
     * Map an article to a category.
     *
     * @param Model $model
     * @param Article $article
     */
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
     * Lazy load Models.
     *
     * @return array|null
     */
    protected function getModels()
    {
        $this->models = $this->models ?? $this->modelManager->getRepository(Model::class)->getAllIndexed();
        return $this->models;
    }
}