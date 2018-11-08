<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 08.11.2018
 * Time: 15:11
 */

namespace MxcDropshipInnocigs\Client;


class PropertyMapper
{
    private $mappings;

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

    public function mapAttributeGroupName($name) {
        return $this->mappings['group_names'][$name] ?? $name;
    }

    public function mapAttributeName($name) {
        return $this->mappings['attribute_names'][$name] ?? $name;
    }
}