<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 08.11.2018
 * Time: 15:11
 */

namespace MxcDropshipInnocigs\Import;


use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Current\Article;

class PropertyMapper
{
    private $mappings;

    /** @var array $articleConfig */
    protected $articleConfig;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array $innocigsBrands */
    private $innocigsBrands = [
        'SC',
        'Steamax',
        'InnoCigs',
    ];

    public function __construct(array $mappings, array $articleConfig, LoggerInterface $log) {
        $this->mappings = $mappings;
        $this->articleConfig = $articleConfig;
        $this->log = $log;
    }

    public function mapArticleName(string $name, string $index, Article $article)
    {
        // article configuration has highest priority
//        $result = $this->articleConfig[$index]['name'];
//        if ($result !== null) return $result;

        // general name mapping applies next
        $result = $this->mappings['article_names'][$name];
        if ($result !== null) return $result;

        // rule based name mapping
        $brand = $article->getBrand();
        if ($brand && in_array($brand, $this->innocigsBrands) && (strpos($name, $brand) !== 0)) {
            $name = $brand . ' ' . $name;
        }
        $parts = $this->mappings['article_name_parts'];
        $search = array_keys($parts);
        $replace = array_values($parts);
        $name = str_replace($search, $replace, $name);
        return $name;
    }

    public function mapArticleCode($code) {
        return $this->mappings['article_codes'][$code] ?? $code;
    }

    public function mapVariantCode($code) {
        return $this->mappings['variant_codes'][$code] ?? $code;
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

    public function mapCategory(string $name, string $index, Article $article)
    {
        // article configuration has highest priority
        $result = $this->articleConfig[$index]['category'];
        if ($result !== null) return $result;

        // general category mapping applies next
        $result = $this->mappings['categories'][$name];
        if ($result !== null) return $result;
        $name = trim($name);

        // rule based category mapping
        if (strpos($name, 'E-Zigaretten') === 0) {
            return $this->addSubCategory('E-Zigaretten', $article->getSupplier());
        } elseif (strpos($name, 'Clearomizer') === 0) {
            return $this->addSubCategory('Verdampfer', $article->getSupplier());
        } elseif (strpos($name, 'Box Mods') === 0) {
            return $this->addSubCategory('Akkuträger', $article->getSupplier());
        } elseif (strpos($name, 'Ladegerät') !== false || strpos($article->getName(), 'Ladegerät') !== false) {
            return $this->addSubCategory('Zubehör > Ladegeräte', $article->getSupplier());
        } elseif( strpos($name, 'Aspire Zubehör') !== false) {
            return $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif( strpos($name, 'Innocigs Zubehör') !== false) {
            return $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif( strpos($name, 'Steamax Zubehör') !== false) {
            return $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif(strpos($article->getName(), 'mAh') != 0) {
            // we had to check Aspire Zubehör, Innocigs Zubehör and Steamax Zubehör upfront
            // because those categories have products with 'mAh' also, but belong to another category
            return $this->addSubCategory('Zubehör > Akku-Zellen', $article->getSupplier());
        } elseif (strpos($name, 'Zubehör') === 0) {
            return $this->addSubCategory('Zubehör', $article->getSupplier());
        } elseif (strpos($name, 'Liquids > Shake and Vape') === 0) {
            return $this->addSubCategory('Shake & Vape', $article->getSupplier());
        } elseif (strpos($name, 'Liquids > Basen & Aromen') === 0) {
            return $this->addSubCategory('Aromen', $article->getSupplier());
        } elseif (strpos($name, 'Liquids > SC > Aromen') === 0) {
            return $this->addSubCategory('Aromen', $article->getBrand());
        } elseif (strpos($name, 'Vampire Vape Aromen') !== false) {
            return 'Aromen > Vampire Vape';
        } elseif (strpos($name, 'VLADS VG') !== false) {
            return 'Liquids > VLADS VG';
        } elseif ($name === 'Liquids') {
            return $this->addSubCategory('Liquids', $article->getSupplier());
        } elseif (strpos($name, 'Basen & Shots') !== false) {
            return $this->addSubCategory('Basen & Shots', $article->getBrand());
        } elseif (strpos($name, 'Basen und Shots') !== false) {
            return $this->addSubCategory('Basen & Shots', $article->getBrand());
        } elseif(strpos($article->getName(), 'Vaporizer') !== false) {
            return $this->addSubCategory('Vaporizer', $article->getBrand());
        } elseif(strpos($name, 'Liquids >') === 0) {
            return $name;
        } else {
            return $this->addSubCategory('Unknown', $name);
        }
    }

    public function mapManufacturer($number, $name)
    {
        $name = trim($name);
        $result = $this->articleConfig[$number];
        if ($name === 'Akkus') return $result;

        if (! isset($result['brand'])) {
            $result['brand'] = $this->mappings['manufacturers'][$name]['brand'] ?? $name;
        }
        if (! isset($result['supplier'])) {
            if (! in_array($name, $this->innocigsBrands)) {
                $result['supplier'] = $this->mappings['manufacturers'][$name]['supplier'] ?? $name;
            }
        }
        return $result;
    }
}
