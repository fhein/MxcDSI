<?php

namespace MxcDropshipInnocigs\Models;

use Zend\Config\Factory;

class ProductRepository extends BaseEntityRepository
{
    protected $productConfigFile = __DIR__ . '/../Config/ProductMappings.config.php';

    protected $dql = [
        'getAllIndexed' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber',

        'getByIds' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p WHERE p.id in (:ids)',

        'getValidVariants' =>
            'SELECT v FROM MxcDropshipInnocigs\Models\Variant v '
            . 'JOIN v.product p '
            . 'JOIN v.options o '
            . 'JOIN o.icGroup g '
            . 'WHERE p.id = :id '
            . 'AND (p.accepted = 1 AND v.accepted = 1 AND o.accepted = 1 AND g.accepted = 1)',

        'getInvalidVariants' =>
            'SELECT v FROM MxcDropshipInnocigs\Models\Variant v '
            . 'JOIN v.product p '
            . 'JOIN v.options o '
            . 'JOIN o.icGroup g '
            . 'WHERE p.id = :id AND '
            . '(p.accepted = 0 OR v.accepted = 0 OR o.accepted = 0 OR g.accepted = 0)',

        'getLinkedProductsHavingOptions'   =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN v.options o '
            . 'JOIN MxcDropshipInnocigs\Models\Group g WITH o.icGroup = g.id '
            . 'WHERE o.id IN (:optionIds) AND p.linked = 1',

        // get all Products which have an associated Shopware article that has related articles with :relatedIds
        'getProductsHavingRelatedArticles' =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
            . 'JOIN p.relatedProducts r  WHERE r.icNumber IN (:relatedIds)',

        // get all Products which have an associated Shopware article that has similar articles with :similarIds
        'getProductsHavingSimilarArticles' =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
            . 'JOIN p.similarProducts a  WHERE p.icNumber IN (:similarIds)',

        // get all Products having flavor property set
        'getProductsWithFlavorSet' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber WHERE p.flavor IS NOT NULL',

        'getProductsWithFlavorMissing' =>
            'SELECT p.name FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber WHERE (p.flavor IS NULL OR p.flavor = \'\') '
            . 'AND p.type IN (\'AROMA\', \'SHAKE_VAPE\', \'LIQUID\') AND p.name NOT LIKE \'%Probierbox%\'',

        'getProductsWithDosageMissing' =>
            'SELECT p.name FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'WHERE (p.dosage IS NULL OR p.dosage = \'\') AND p.type = \'AROMA\'',

        'getArticle' =>
            'SELECT DISTINCT a FROM Shopware\Models\Article\Article a '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.article = a.id '
            . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.number = d.number '
            . 'JOIN MxcDropshipInnocigs\Models\Product p WHERE v.product = p.id '
            . 'WHERE p.number = :number',

        'removeOrphaned' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p WHERE p.variants IS EMPTY',

        'setStateByIds' =>
            'UPDATE MxcDropshipInnocigs\Models\Product p SET p.:state = :value WHERE p.id IN (:ids)',

        // DQL does not support parameters in SELECT
        'getProperties'  =>
            'SELECT :properties FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber',

        // DQL does not support parameters in SELECT
        'getPropertiesById'  =>
            'SELECT :properties FROM MxcDropshipInnocigs\Models\Product p WHERE p.id = :id',

        'getProductsByType' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber WHERE p.type = :type',

        // get all Products which need a flavor setting
        'getFlavoredProducts' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'WHERE p.type IN (\'AROMA\', \'SHAKE_VAPE\', \'LIQUID\') AND p.name NOT LIKE \'%Probierbox%\'',

        'getExcelExportMapping' =>
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name, p.commonName '
            . 'FROM MxcDropshipInnocigs\Models\Product p',

        'getExcelExportAroma' =>
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name, p.dosage '
            . 'FROM MxcDropshipInnocigs\Models\Product p WHERE p.type = \'AROMA\'',

        'getExcelExportDescription' =>
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name, p.description '
            . 'FROM MxcDropshipInnocigs\Models\Product p',

        'getExcelExportFlavoredProducts' =>
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name, p.flavor '
            . 'FROM MxcDropshipInnocigs\Models\Product p '
            . 'WHERE p.type IN (\'AROMA\', \'SHAKE_VAPE\', \'LIQUID\') AND p.name NOT LIKE \'%Probierbox%\'',
    ];

    protected $sql = [
        'refreshLinks' =>
            'UPDATE s_plugin_mxc_dsi_product p '
            . 'INNER JOIN s_plugin_mxc_dsi_variant v ON v.product_id = p.id '
            . 'LEFT JOIN s_articles_details d ON d.ordernumber = v.number '
            . 'SET p.linked = NOT ISNULL(d.ordernumber), p.active = NOT ISNULL(d.orderNumber)',
    ];

    private $mappedProperties = [
        'icNumber',
        'number',
        'name',
        'commonName',
        'type',
        'category',
        'supplier',
        'brand',
        'piecesPerPack',
        'flavor',
        'dosage',
        'base',
        'retailPriceDampfplanet',
        'retailPriceOthers'
    ];

    public function getByIds(array $ids)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('ids', $ids)
            ->getResult();
    }

    public function getProductsByType(string $type)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('type', $type)
            ->getResult();
    }

    public function setStateByIds(string $property, bool $value, array $ids)
    {
        $dql = str_replace(':state', $property, $this->dql[__FUNCTION__]);
        return $this->getEntityManager()->createQuery($dql)
            ->setParameters(['value' => $value, 'ids' => $ids])
            ->execute();
    }

    public function getInvalidVariants(Product $product)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('id', $product->getId())
            ->getResult();
    }

    public function getProductsHavingRelatedArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('relatedIds', $relatedIds)
            ->getResult();
    }

    public function getProductsHavingSimilarArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('similarIds', $relatedIds)->getResult();
    }

    public function getLinkedProductsHavingOptions($optionIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('optionIds', $optionIds)->getResult();
    }

    public function getArticle(Product $product)
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('number', $product->getNumber())->getResult();
        return $result[0] ?? null;
    }

    public function removeOrphaned()
    {
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Product $orphan */
        $em = $this->getEntityManager();
        foreach ($orphans as $orphan) {
            $this->log->debug('Removing orphaned product \'' . $orphan->getName() . '\'');
            $em->remove($orphan);
        }
    }

    public function exportMappedProperties(string $productConfigFile = null)
    {
        $productConfigFile = $productConfigFile ?? $this->productConfigFile;
        $propertyMappings = $this->getMappedProperties();
        if (!empty($propertyMappings)) {
            /** @noinspection PhpUndefinedFieldInspection */
            Factory::toFile($productConfigFile, $propertyMappings);

            $this->log->debug(sprintf("Exported %s product mappings to Config\\%s.",
                count($propertyMappings),
                basename($productConfigFile)
            ));
        }
    }

    public function getMappedProperties()
    {
        return $this->getProperties($this->mappedProperties);
    }

    protected function getPropertiesQuery(array $properties, string $dql)
    {
        $properties = array_map(function($property) {
            return 'p.' . $property;
        }, $properties);
        $properties = implode(', ', $properties);

        $dql = str_replace(':properties', $properties, $dql);
        return $this->getEntityManager()->createQuery($dql);
    }

    public function getProperties(array $properties)
    {
        return $this->getPropertiesQuery($properties, $this->dql[__FUNCTION__])->getResult();
    }

    public function getPropertiesById(array $properties, array $id) {
        return $this->getPropertiesQuery($properties, $this->dql[__FUNCTION__])
            ->setParameter('id', $id)
            ->getResult();
    }

    /**
     * A Product validates true if either it's $accepted member is true
     * and at least one of the article's variants validates true
     *
     * @param Product $product
     * @return bool
     */
    public function validate(Product $product): bool
    {
        if (!$product->isAccepted()) {
            return false;
        }
        return (!empty($this->getValidVariants($product)));
    }

    public function getValidVariants(Product $product)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('id', $product->getId())
            ->getResult();
    }
}
