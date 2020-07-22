<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use Shopware\Models\Article\Article;
use Zend\Config\Factory;

class ProductRepository extends BaseEntityRepository
{
    protected $productConfigFile = __DIR__ . '/../Config/ProductMappings.config.php';

    protected $dql = [
        'getAllIndexed' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber',

        'getLinkedProductIds' =>
            'SELECT DISTINCT p.id FROM MxcDropshipInnocigs\Models\Product p '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number',

        'getProductsByIds' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p WHERE p.id in (:ids)',

        'getLinkedProducts'   =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number',

        'getArticlesWithoutProduct' =>
            'SELECT DISTINCT a FROM Shopware\Models\Article\Article a '
            . 'JOIN a.details d '
            . 'LEFT JOIN MxcDropshipInnocigs\Models\Variant v WITH v.number = d.number '
            . 'WHERE v.id IS NULL',

        'getProduct' =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
            //. 'JOIN Shopware\Models\Article\Article a WITH d.article = a.id AND a.id = :id'
            . 'WHERE d.article = :id',

        'getArticle' =>
            'SELECT DISTINCT a FROM Shopware\Models\Article\Article a '
            . 'JOIN a.details d '
            . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.number = d.number '
            . 'JOIN MxcDropshipInnocigs\Models\Product p WITH v.product = p.id '
            . 'WHERE p.number = :number',

        'getLinkedProductsFromProductIds'   =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
            . 'WHERE p.id IN (:productIds)',

        'getProductIdsByOptionIds'   =>
            'SELECT DISTINCT p.id FROM MxcDropshipInnocigs\Models\Product p '
            . 'JOIN p.variants v '
            . 'JOIN v.options o '
            . 'JOIN MxcDropshipInnocigs\Models\Group g WITH o.icGroup = g.id '
            . 'WHERE o.id IN (:optionIds)',

        'getLinkedProductsHavingOptions'   =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN v.options o '
            . 'JOIN MxcDropshipInnocigs\Models\Group g WITH o.icGroup = g.id '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
            . 'WHERE o.id IN (:optionIds)',

        // get all products which have an associated Shopware article and which have a related product from :relatedIds
        'getProductsHavingRelatedArticles' =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
            . 'JOIN p.relatedProducts r  WHERE r.id IN (:relatedIds)',

        'getProductsWithReleaseDate' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p WHERE p.releaseDate IS NOT NULL',

        // get all products which have an associated Shopware article and which have a similar product from :similarIds
        'getProductsHavingSimilarArticles' =>
            'SELECT DISTINCT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'JOIN p.variants v '
            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
            . 'JOIN p.similarProducts s  WHERE s.id IN (:similarIds)',

        // get all Products having flavor property set
        'getProductsWithFlavorSet' =>
            'SELECT p FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber WHERE p.flavor IS NOT NULL',

        'getProductsWithFlavorMissing' =>
            'SELECT p.name FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber WHERE (p.flavor IS NULL OR p.flavor = \'\') '
            . 'AND p.type IN (\'AROMA\', \'SHAKE_VAPE\', \'LIQUID\') AND p.name NOT LIKE \'%Probierbox%\'',

        'getProductsWithDosageMissing' =>
            'SELECT p.name FROM MxcDropshipInnocigs\Models\Product p INDEX BY p.icNumber '
            . 'WHERE (p.dosage IS NULL OR p.dosage = \'\') AND p.type = \'AROMA\'',

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
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name, p.description, p.icDescription '
            . 'FROM MxcDropshipInnocigs\Models\Product p',

        'getExcelExportEcigMetaData' =>
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name, p.power, p.headChangeable '
            . 'FROM MxcDropshipInnocigs\Models\Product p '
            . 'WHERE p.type IN (\'E_CIGARETTE\', \'POD_SYSTEM\', \'E_PIPE\')',

        'getExcelExportFlavoredProducts' =>
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name, p.flavor, p.content, p.capacity '
            . 'FROM MxcDropshipInnocigs\Models\Product p '
            . 'WHERE p.type IN (\'AROMA\', \'SHAKE_VAPE\', \'LIQUID\', \'EASY3_CAP\')',

        'getExcelExportNewProducts' =>
            'SELECT p.icNumber, p.type, p.supplier, p.brand, p.name '
            . 'FROM MxcDropshipInnocigs\Models\Product p '
            . 'WHERE p.new = 1',
    ];

    protected $sql = [
        'updateLinkState' =>
            'UPDATE s_plugin_mxc_dsi_product p '
            . 'JOIN s_plugin_mxc_dsi_variant v ON v.product_id = p.id '
            . 'LEFT JOIN s_articles_details d ON d.ordernumber = v.number '
            . 'SET p.linked = NOT ISNULL(d.ordernumber), p.active = NOT ISNULL(d.ordernumber)',
        'updateActiveState' =>
            'UPDATE s_plugin_mxc_dsi_product p '
            . 'JOIN s_plugin_mxc_dsi_variant v ON v.product_id = p.id '
            . 'JOIN s_articles_details d ON d.ordernumber = v.number '
            . 'JOIN s_articles a ON d.articleID = a.id '
            . 'SET p.active = a.active',
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
        'content',
        'capacity',
        'flavor',
        'flavorCategory',
        'dosage',
        'base',
        'power',
        'cellCount',
        'cellCapacity',
        'headChangeable',
        'description',
    ];

    public function refreshProductStates()
    {
        $this->getStatement('updateLinkState')->execute();
        $this->getStatement('updateActiveState')->execute();
    }

    public function getProductsByIds(array $ids)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('ids', $ids)
            ->getResult();
    }

    public function setStateByIds(string $property, bool $value, array $ids)
    {
        $dql = str_replace(':state', $property, $this->dql[__FUNCTION__]);
        return $this->getEntityManager()->createQuery($dql)
            ->setParameters(['value' => $value, 'ids' => $ids])
            ->execute();
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

    public function getProductIdsByOptionIds($optionIds)
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('optionIds', $optionIds)->getScalarResult();
        return array_column($result, 'id');
    }

    public function getLinkedProductIds() {
        $result = $this->getQuery(__FUNCTION__)->getScalarResult();
        return array_column($result, 'id');
    }

    public function getLinkedProductsFromProductIds($productIds) {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('productIds', $productIds)->getResult();
    }

    public function getLinkedProductsByOptionIds($optionIds) {
        $productIds = $this->getProductIdsByOptionIds($optionIds);
        return $this->getLinkedProductsFromProductIds($productIds);
    }

    public function getArticle(Product $product)
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('number', $product->getNumber())->getResult();
        return $result[0] ?? null;
    }

    public function getProduct(Article $article)
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('id', $article->getId())->getResult();
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
        $currentMappings = [];
        if (file_exists($productConfigFile)) {
            /** @noinspection PhpIncludeInspection */
            $currentMappings = include $productConfigFile;
        }
        if (! empty($propertyMappings)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $propertyMappings = array_replace_recursive($currentMappings, $propertyMappings);
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
        if (! $product->isAccepted()) return false;
        foreach ($product->getVariants() as $variant) {
            if ($variant->isValid()) return true;
        }
        return false;
    }
}
