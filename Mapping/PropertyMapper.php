<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 08.11.2018
 * Time: 15:11
 */

namespace MxcDropshipInnocigs\Mapping;


use MxcDropshipInnocigs\Models\Current\Article;

class PropertyMapper
{
    private $mappings;

    private $innocigsBrands = [
        'SC',
        'Steamax',
        'InnoCigs'
    ];

    public function __construct(array $mappings) {
        $this->mappings = $mappings;
    }

    public function mapArticleName($name) {
        return $this->mappings['article_names'][$name] ?? $name;
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
        return $this->mappings['option_names'][$name] ?? $name;
    }

    public function mapCategory($name, Article $article)
    {
        if (isset($this->mappings['categories'][$name])) {
            return $this->mappings['categories'][$name];
        }

        $supplier = $article->getSupplier();

        if (strpos($name, 'E-Zigaretten') === 0) {
            return 'E-Zigaretten > ' . $supplier;
        } elseif (strpos($name, 'Clearomizer') === 0) {
            return 'Verdampfer > ' . $supplier;
        } elseif (strpos($name, 'Box Mods') === 0) {
            return 'Akkuträger > ' . $supplier;
        } elseif (strpos($name, 'Ladegerät') !== false || strpos($article->getName(), 'Ladegerät') !== false) {
            return 'Zubehör > Ladegeräte > ' . $supplier;
        } elseif( strpos($name, 'Aspire Zubehör') !== false) {
            return 'Zubehör > ' . $supplier;
        } elseif( strpos($name, 'Innocigs Zubehör') !== false) {
            return 'Zubehör > ' . $supplier;
        } elseif( strpos($name, 'Steamax Zubehör') !== false) {
            return 'Zubehör > ' . $supplier;
        } elseif(strpos($article->getName(), 'mAh') !== 0) {
            // we had to check Aspire Zubehör, Innocigs Zubehör and Steamax Zubehör upfront
            // because those categories have products with 'mAh' also, but belong to another category
            return 'Zubehör > Akku-Zellen > ' . $supplier;
        } elseif (strpos($name, 'Zubehör') === 0) {
            return 'Zubehör > ' . $supplier;
        } elseif (strpos($name, 'Liquids > Shake and Vape') === 0) {
            return 'Shake & Vape > ' . $supplier;
        } elseif (strpos($name, 'Liquids > Basen & Aromen') === 0) {
            return 'Aromen > ' . $supplier;
        } elseif (strpos($name, 'Liquids > SC > Aromen') === 0) {
            return 'Aromen > ' . $article->getBrand();
        } elseif (strpos($name, 'Vampire Vipe Aromen') !== false) {
            return 'Aromen > Vampire Vape';
        } elseif (strpos($name, 'VLADS VG') !== false) {
            return 'Liquids > VLADS VG';
        } elseif (strpos($name, 'Basen & Shots') !== false) {
            return 'Basen & Shots > ' . $article->getBrand();
        } elseif ($name === '') {
            return ('Unknown');
        }
        return $name;
    }

    public function mapManufacturer($name)
    {
        $name = trim($name);
        if ($name === 'Akkus') {
            return [];
        }
        if (isset($this->mappings['manufacturers'][$name])) {
            return $this->mappings['manufacturers'][$name];
        }
        $result ['brand' ] = $name;
        if (! in_array($name, $this->innocigsBrands)) {
            $result['supplier'] = $name;
        }
        return $result;
    }
}
