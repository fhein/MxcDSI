<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\ArrayMap;
use MxcDropshipInnocigs\Import\Report\ArrayReport;
use MxcDropshipInnocigs\Import\Report\Mapper\SuccessiveReplacer;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;

class PropertyMapper
{
    /** @var BulkOperation  */
    protected $bulkOperation;

    /** @var ArrayReport */
    protected $reporter;
    /** @var LoggerInterface $log */
    protected $log;
    protected $mismatchedOptionNames = [];
    protected $nameTrace = [];
    protected $categoryMap = [];
    protected $brands = [];
    protected $suppliers = [];
    private   $config;

    public function __construct(BulkOperation $bulkOperation, ArrayReport $reporter, array $config, LoggerInterface $log) {
        $this->config = $config;
        $this->log = $log;
        $this->bulkOperation = $bulkOperation;
        $this->reporter = $reporter;
    }

    public function modelToArticle(Model $model, Article $article) {
        $number = $model->getMaster();
        $article->setNumber($this->config['article_codes'][$number] ?? $number);
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
        $result = $this->config['articles'][$article->getNumber()];
        $article->setBrand($result['brand'] ?? $this->config['manufacturers'][$manufacturer]['brand'] ?? $manufacturer);
        $supplier = $result['supplier'];
        if (! $supplier) {
            if (! in_array($manufacturer, $this->config['innocigs_brands'])) {
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

        foreach($this->config['article_name_replacements'] as $replacer => $replacements) {
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
        $result = $this->config['articles'][$article->getNumber()]['category'] ?? $this->config['categories'][$name];
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
        $variant->setNumber($this->config['variant_codes'][$number] ?? $number);
        $variant->setIcNumber($number);
        $variant->setEan($model->getEan());
        $price = floatval(str_replace(',', '.', $model->getPurchasePrice()));
        $variant->setPurchasePrice($price);
        $price = floatVal(str_replace(',', '.', $model->getRetailPrice()));
        $variant->setRetailPrice($price);
    }

    public function mapGroupName($name) {
        return $this->config['group_names'][$name] ?? $name;
    }

    public function mapOptionName($name) {
        $mapping = $this->config['option_names'][$name] ?? $name;
        return str_replace('weiss', 'weiß', $mapping);
    }

    public function applyFilters() {
        foreach($this->config['filters']['update'] as $filter) {
            $this->bulkOperation->update($filter);
        }
    }

    public function logMappingResults() {

        ksort($this->brands);
        ksort($this->suppliers);
        ksort($this->nameTrace);
        ksort($this->mismatchedOptionNames);

        $unchangedArticleNames = array_map(function($value) {
                return ($value['imported'] === $value['mapped']);
            }, $this->nameTrace);
        $unchangedArticleNames = array_keys(array_filter(
            $unchangedArticleNames,
            function($value) { return $value === true; }
        ));

        $namesWithoutRemovedOptions = array_map(function($value) {
            return ($value['imported'] === $value['options_removed']);
        }, $this->nameTrace);
        $namesWithoutRemovedOptions = array_keys(array_filter($namesWithoutRemovedOptions, function($value) { return $value === true; }));

        $optionMappingIssues = array_filter($this->mismatchedOptionNames, function($value) {
            false === $value['fixAvailable'] || false === $value['fixApplied'];
        });

        $nameMap = array_values(array_map(function($value) {
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
                    'replacer' => 'preg_replace',
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
                    'replacer' => 'str_replace',
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
        ];

        $mapReport = new ArrayReport();
        $mapReport($topics);
    }
}
