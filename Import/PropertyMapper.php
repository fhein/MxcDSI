<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\MappingReport;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

class PropertyMapper
{
    /** @var BulkOperation  */
    protected $bulkOperation;
    /** @var LoggerInterface $log */
    protected $log;
    protected $mismatchedOptionNames = [];
    protected $unmappedArticleNames = [];
    protected $usedStrReplacements = [];
    protected $usedPregReplacements = [];
    protected $nameMap = [];
    protected $nameWithoutOptionsMap = [];
    protected $categoryMap = [];
    protected $brands = [];
    protected $suppliers = [];
    private $mappings;
    /** @var array $innocigsBrands */
    private $innocigsBrands = [
        'SC',
        'Steamax',
        'InnoCigs',
    ];

    public function __construct(array $mappings, BulkOperation $bulkOperation, LoggerInterface $log) {
        $this->mappings = $mappings;
        $this->log = $log;
        $this->bulkOperation = $bulkOperation;
    }

    public function modelToArticle(Model $model, Article $article) {
        $number = $model->getMaster();
        $article->setNumber($this->mappings['article_codes'][$number] ?? $number);
        $article->setIcNumber($number);
        $article->setManualUrl($model->getManualUrl());
        $article->setImageUrl($model->getImageUrl());
        $this->mapManufacturer($article, $model->getManufacturer());
        $this->mapArticleName($model, $article);
        // this has to be last because it depends on the article properties
        $this->mapCategory($article, $model->getCategory());
    }

    public function mapManufacturer(Article $article, string $manufacturer): void
    {
        $result = $this->mappings['articles'][$article->getNumber()];
        $article->setBrand($result['brand'] ?? $this->mappings['manufacturers'][$manufacturer]['brand'] ?? $manufacturer);
        $supplier = $result['supplier'];
        if (! $supplier) {
            if (! in_array($manufacturer, $this->innocigsBrands)) {
                $supplier = $this->mappings['manufacturers'][$manufacturer]['supplier'] ?? $manufacturer;
            }
        }
        $article->setSupplier($supplier);
        $article->setManufacturer($manufacturer);
        $this->brands[$article->getBrand()] = true;
        $this->suppliers[$article->getSupplier()] = true;
    }

    public function mapArticleName(Model $model, Article $article): void
    {
        $nameBefore = $model->getName();

        $name = $this->removeOptionsFromArticleName($model);
        $nameBeforeWithoutOptions = $name;

        // general name mapping applied first
        $result = $this->mappings['article_names'][$model->getName()];
        if ($result !== null) {
            $article->setName($result);
            return;
        }

        // rule based name mapping applied next
        $brand = $article->getBrand();
        if ($brand && in_array($brand, $this->innocigsBrands) && (strpos($name, $brand) !== 0)) {
            $name = $brand . ' - ' . $name;
        }
        $name = trim($this->replaceNameParts($name));
        $article->setName($name);

        if ($name === $nameBefore) {
            $this->unmappedArticleNames[$name] = true;
        }
        $this->nameMap[$nameBefore] = $name;
        $this->nameWithoutOptionsMap[$nameBefore] = $nameBeforeWithoutOptions;
    }

    public function removeOptionsFromArticleName(Model $model)
    {
        // Innocigs variant names include variant descriptions
        // We take the first variant's name and remove the variant descriptions
        // in order to extract the real article name
        $options = explode('##!##', $model->getOptions());
        $name = $model->getName();

        foreach ($options as $option) {
            $option = explode( '#!#', $option)[1];

            // '1er Packung' is not a substring of any article name
            if ($option === '1er Packung') continue;

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

    protected function applyOptionNameMapping(string $model, string $name, string $option) {
        // They introduced some cases where the option name is not equal
        // to the string added to the article name, so we have to check
        // that, also. The implementation here is a hack right now.
        $o = $this->mappings['article_name_option_fixes'][$option] ?? null;
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
            'name' => $name,
            'option' => $option,
            'fixAvailable' => $fixAvailable,
            'fixApplied' => $fixApplied,
        ];
        return $name;
    }

    public function mapCategory(Article $article, ?string $name) : void
    {
        if (null === $name) {
            $article->setCategory('Unknown');
            return;
        }
        // article configuration has highest priority
        // general category mapping applies next
        $result = $this->mappings['articles'][$article->getNumber()]['category'] ?? $this->mappings['categories'][$name];
        if ($result !== null) {
            $article->setCategory($result);
            $this->categoryMap[$name] = $result;
            return;
        }

        // rule based category mapping
        if (strpos($name, 'E-Zigaretten') === 0) {
            $category = $this->addSubCategory('E-Zigaretten', $article->getSupplier());
        } elseif (strpos($name, 'Clearomizer') === 0) {
            $category = $this->addSubCategory('Verdampfer', $article->getSupplier());
        } elseif (strpos($name, 'Box Mods') === 0) {
            $category = $this->addSubCategory('Akkuträger', $article->getSupplier());
        } elseif (strpos($name, 'Ladegerät') !== false || strpos($article->getName(), 'Ladegerät') !== false) {
            $category = $this->addSubCategory('Zubehör > Ladegeräte', $article->getSupplier());
        } elseif( strpos($name, 'Aspire Zubehör') !== false) {
            $category = $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif( strpos($name, 'Innocigs Zubehör') !== false) {
            $category = $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif( strpos($name, 'Steamax Zubehör') !== false) {
            $category = $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif(strpos($article->getName(), 'mAh') != 0) {
            // we had to check Aspire Zubehör, Innocigs Zubehör and Steamax Zubehör upfront
            // because those categories have products with 'mAh' also, but belong to another category
            $category = $this->addSubCategory('Zubehör > Akku-Zellen', $article->getSupplier());
        } elseif (strpos($name, 'Zubehör') === 0) {
            $category = $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif (strpos($name, 'Liquids > Shake and Vape') === 0) {
            $category = $this->addSubCategory('Shake & Vape', $article->getSupplier());
        } elseif (strpos($name, 'Liquids > Basen & Aromen') === 0) {
            $category = $this->addSubCategory('Aromen', $article->getSupplier());
        } elseif (strpos($name, 'Liquids > SC > Aromen') === 0) {
            $category = $this->addSubCategory('Aromen', $article->getBrand());
        } elseif (strpos($name, 'Vampire Vape Aromen') !== false) {
            $category = 'Aromen > Vampire Vape';
        } elseif (strpos($name, 'VLADS VG') !== false) {
            $category = 'Liquids > VLADS VG';
        } elseif ($name === 'Liquids') {
            $category = $this->addSubCategory('Liquids', $article->getSupplier());
        } elseif (strpos($name, 'Basen & Shots') !== false) {
            $category = $this->addSubCategory('Basen & Shots', $article->getBrand());
        } elseif (strpos($name, 'Basen und Shots') !== false) {
            $category = $this->addSubCategory('Basen & Shots', $article->getBrand());
        } elseif(strpos($article->getName(), 'Vaporizer') !== false) {
            $category = $this->addSubCategory('Vaporizer', $article->getBrand());
        } elseif(strpos($name, 'Liquids >') === 0) {
            $category = $name;
        } else {
            $category = $this->addSubCategory('Unknown', $name);
        }
        $this->categoryMap[$name] = $category;
        $article->setCategory($category);
    }

    protected function addSubCategory(string $name, ?string $subcategory)
    {
        if ($subcategory !== null && $subcategory !== '') {
            $name .= ' > ' . $subcategory;
        }
        return $name;
    }

    public function modelToVariant(Model $model, Variant $variant) {
        $number = $model->getModel();
        $variant->setNumber($this->mappings['variant_codes'][$number] ?? $number);
        $variant->setIcNumber($number);
        $variant->setEan($model->getEan());
        $price = floatval(str_replace(',', '.', $model->getPurchasePrice()));
        $variant->setPurchasePrice($price);
        $price = floatVal(str_replace(',', '.', $model->getRetailPrice()));
        $variant->setRetailPrice($price);
    }

    public function mapGroupName($name) {
        return $this->mappings['group_names'][$name] ?? $name;
    }

    public function mapOptionName($name) {
        $mapping = $this->mappings['option_names'][$name] ?? $name;
        return str_replace('weiss', 'weiß', $mapping);
    }

    public function applyFilters() {
        foreach($this->mappings['filters']['update'] as $filter) {
            $this->bulkOperation->update($filter);
        }
    }

    public function log() {
        $topics = [
            'mismatchedOptionNames' => $this->mismatchedOptionNames,
            'nameWithoutOptionsMap' => $this->nameWithoutOptionsMap,
            'categoryMap'           => $this->categoryMap,
            'nameMap'               => $this->nameMap,
            'suppliers'             => $this->suppliers,
            'brands'                => $this->brands,
            'usedStrReplacements'   => $this->usedStrReplacements,
            'usedPregReplacements'  => $this->usedPregReplacements,
        ];
        $mapReport = new MappingReport();
        $mapReport->report($topics);
        $this->logOptionNameIssues();
    }

    protected function logOptionNameIssues()
    {
        foreach ($this->mismatchedOptionNames as $optionLog) {
            if (false === $optionLog['fixAvailable']) {
                $this->log->warn(sprintf(
                    'Model name \'%s\' does not contain the option name \'%s\' and there is no option name mapping specified.',
                    $optionLog['name'],
                    $optionLog['option']
                ));
                continue;
            }
            if (false === $optionLog['fixApplied']) {
                $this->log->warn(sprintf(
                    'Model name \'%s\' does not contain the option name \'%s\' and the option name mapping does not apply.',
                    $optionLog['name'],
                    $optionLog['option']
                ));
            }
        }
        foreach ($this->unmappedArticleNames as $name => $_)  {
            $this->log->warn('Unmapped article name: ' . $name);
        }
    }

    /**
     * @param string $name
     * @return mixed|string|string[]|null
     */
    protected function replaceNameParts(string $name)
    {
        $parts = $this->mappings['article_name_parts_rexp'];
        if (null !== $parts) {
            $search = array_keys($parts);
            $replace = array_values($parts);
            $name = preg_replace($search, $replace, $name);
//            // This is the explicit implementation of the preg_replace operation above.
//            // Disable the above str_replace and enable this block if you want to check
//            // which article_name_parts replacements are actually used.
//            $count = count($search);
//            for ($i = 0; $i < $count; $i++) {
//                if (strpos($name, $search[$i]) !== false) {
//                    $name = preg_replace($search[$i], $replace[$i], $name);
//                    $this->usedPregReplacements[$search[$i]] = true;
//                }
//            }
        }
        $parts = $this->mappings['article_name_parts'];
        if (null !== $parts) {
            $search = array_keys($parts);
            $replace = array_values($parts);
            $name = str_replace($search, $replace, $name);
//            // This is the explicit implementation of the str_replace operation above.
//            // Disable the above str_replace and enable this block if you want to check
//            // which article_name_parts replacements are actually used.
//            $count = count($search);
//            for ($i = 0; $i < $count; $i++) {
//                if (strpos($name, $search[$i]) !== false) {
//                    $name = str_replace($search[$i], $replace[$i], $name);
//                    $this->usedStrReplacements[$search[$i]] = true;
//                }
//            }
        }
        return $name;
    }
}
