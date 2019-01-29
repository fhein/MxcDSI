<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 08.11.2018
 * Time: 15:11
 */

namespace MxcDropshipInnocigs\Import;


use Mxc\Shopware\Plugin\Database\BulkOperation;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use Zend\Config\Factory;

class PropertyMapper
{
    private $mappings;

    /** @var array $articleConfig */
    protected $articleConfig;

    /** @var BulkOperation  */
    protected $bulkOperation;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array $innocigsBrands */
    private $innocigsBrands = [
        'SC',
        'Steamax',
        'InnoCigs',
    ];

    protected $mismatchedOptionNames = [];
    protected $categoryMap = [];

    public function __construct(array $mappings, array $articleConfig, BulkOperation $bulkOperation, LoggerInterface $log) {
        $this->mappings = $mappings;
        $this->articleConfig = $articleConfig;
        $this->log = $log;
        $this->bulkOperation = $bulkOperation;
    }

    public function mapArticleName(Article $article, string $name): void
    {
        // article configuration has highest priority
//        $result = $this->articleConfig[$index]['name'];
//        if ($result !== null) return $result;

        // general name mapping applies next
        $result = $this->mappings['article_names'][$name];
        if ($result !== null) {
            $article->setName($result);
            return;
        }

        // rule based name mapping
        $brand = $article->getBrand();
        if ($brand && in_array($brand, $this->innocigsBrands) && (strpos($name, $brand) !== 0)) {
            $name = $brand . ' ' . $name;
        }
        $parts = $this->mappings['article_name_parts'];
        $search = array_keys($parts);
        $replace = array_values($parts);
        $name = str_replace($search, $replace, $name);
        $article->setName($name);
    }

    public function mapGroupName($name) {
        return $this->mappings['group_names'][$name] ?? $name;
    }

    public function mapOptionName($name) {
        $mapping = $this->mappings['option_names'][$name] ?? $name;
        return str_replace('weiss', 'weiß', $mapping);
    }

    protected function addSubCategory(string $name, ?string $subcategory)
    {
        if ($subcategory !== null && $subcategory !== '') {
            $name .= ' > ' . $subcategory;
        }
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
        $result = $this->articleConfig[$article->getNumber()]['category'] ?? $this->mappings['categories'][$name];
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

    public function mapManufacturer(Article $article, $name): void
    {
        $result = $this->articleConfig[$article->getNumber()];
        $article->setBrand($result['brand'] ?? $this->mappings['manufacturers'][$name]['brand'] ?? $name);
        $supplier = $result['supplier'];
        if (! $supplier) {
            if (! in_array($name, $this->innocigsBrands)) {
                $supplier = $this->mappings['manufacturers'][$name]['supplier'] ?? $name;
            }
        }
        $article->setSupplier($supplier);
        $article->setManufacturer($name);
    }

    public function removeOptionsFromArticleName(Model $model)
    {
        // Innocigs variant names include variant descriptions
        // We take the first variant's name and remove the variant descriptions
        // in order to extract the real article name
        $options = explode('##!##', $model->getOptions());
        $name = $model->getName();
        $model = $model->getModel();

        foreach ($options as $option) {
            $option = explode( '#!#', $option)[1];
            if ($option === '1er Packung') continue;

            if (strpos($name, $option) !== false) {
                $name = str_replace($option, '', $name);
            } else {
                $this->mismatchedOptionNames[$model] = [
                    'name' => $name,
                    'option' => $option
                ];
                // They introduced some cases where the option name is not equal
                // to the string added to the article name, so we have to check
                // that, also. The implementation here is a hack right now.
                $o = $this->mappings['article_name_option_fixes'][$option] ?? null;
                if ($o) {
                    $name = str_replace($o, '', $name);
                } else {
                    $this->log->warn(sprintf(
                        'Model name \'%s\' does not contain the option name \'%s\' and there is no option name mapping specified.',
                        $name,
                        $option
                    ));
                }
            }
        }
        $name = trim($name);
        if (substr($name, -2) === ' -') {
            $name = substr($name, 0, strlen($name) - 2);
        }
        return trim($name);
    }

    public function modelToArticle(Model $model, Article $article) {
        $number = $model->getMaster();
        $article->setNumber($this->mappings['article_codes'][$number] ?? $number);
        $article->setIcNumber($number);
        $article->setManualUrl($model->getManualUrl());
        $article->setImageUrl($model->getImageUrl());
        $name = $this->removeOptionsFromArticleName($model);
        $this->mapManufacturer($article, $model->getManufacturer());
        $this->mapArticleName($article, $name);

        // this has to be last because it depends on the article properties
        $this->mapCategory($article, $model->getCategory());
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

    public function log() {
        Factory::toFile(__DIR__ . '/../Dump/option.name.mismatches.php', $this->mismatchedOptionNames);
        Factory::toFile(__DIR__ . '/../Dump/category.map.php', $this->categoryMap);
    }

    public function getMismatchedOptionNames() {
        return $this->mismatchedOptionNames;
    }

    public function applyFilters() {
        foreach($this->mappings['filter']['update'] as $filter) {
            $this->bulkOperation->update($filter);
        }
    }
}
