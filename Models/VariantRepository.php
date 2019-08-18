<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use Zend\Config\Factory;

class VariantRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'             => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v INDEX BY v.icNumber',
        'getDetail'                 => 'SELECT d FROM Shopware\Models\Article\Detail d WHERE d.number = :ordernumber',
        'removeOrphaned'            => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v WHERE v.product IS NULL',
        'getVariantsWithoutModel'   => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v '
                                        . 'LEFT JOIN MxcDropshipInnocigs\Models\Model m WITH m.model = v.icNumber '
                                        . 'WHERE m.id IS NULL',

        // DQL does not support parameters in SELECT
        'getProperties'  =>
            'SELECT :properties FROM MxcDropshipInnocigs\Models\Variant p INDEX BY p.icNumber',

        // DQL does not support parameters in SELECT
        'getPropertiesById'  =>
            'SELECT :properties FROM MxcDropshipInnocigs\Models\Variant p WHERE p.id = :id',

    ];

    protected $sql = [
        'removeOptions' => 'DELETE FROM s_plugin_mxc_dsi_x_variants_options WHERE variant_id = ?',
    ];

    protected $variantConfigFile = __DIR__ . '/../Config/VariantMappings.config.php';

    private $mappedProperties = [
        'icNumber',
        'capacity',
        'retailPriceDampfplanet',
        'retailPriceMaxVapor',
        'retailPriceOthers',
    ];

    public function getDetail(Variant $variant)
    {
        $result = $this->getQuery(__FUNCTION__)
            ->setParameter('ordernumber', $variant->getNumber())
            ->getResult();
        return $result[0] ?? null;
    }

    public function removeOptions(Variant $variant)
    {
        $stmnt = $this->getStatement(__FUNCTION__);
        $stmnt->bindValue(1, $variant->getId());
        $stmnt->execute();
    }

    public function removeOrphaned()
    {
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Variant $orphan */
        $em = $this->getEntityManager();
        foreach($orphans as $orphan) {
            $this->log->debug('Removing orphaned variant \'' . $orphan->getNumber() .'\'');
            $em->remove($orphan);
        }
    }

    public function getPiecesPerOrder(Variant $variant) {
        $options = $variant->getOptions();
        $matches = [];
        $pieces = 1;
        foreach ($options as $option) {
            preg_match('~(\d+)er Packung~', $option->getName(), $matches);
            if (empty($matches)) {
                continue;
            }
            $pieces =  $matches[1];
        }
        return $pieces;
    }

    /**
     * A variant validates true if the $accepted member of the variant is true and
     * the $accepted member of the associated Product is true and all of the variant's
     * options validate true
     *
     * @param Variant $variant
     * @return bool
     */
    public function validate(Variant $variant) : bool
    {
        if (! ($variant->isAccepted() && $variant->getProduct()->isAccepted())) {
            return false;
        }
        $options = $variant->getOptions();
        /** @var Option $option */
        foreach ($options as $option) {
            if (! $option->isValid()) return false;
        }
        return true;
    }

    public function exportMappedProperties(string $variantConfigFile = null)
    {
        $variantConfigFile = $variantConfigFile ?? $this->variantConfigFile;
        $propertyMappings = $this->getMappedProperties();
        $currentMappings = [];
        if (file_exists($variantConfigFile)) {
            /** @noinspection PhpIncludeInspection */
            $currentMappings = include $variantConfigFile;
        }
        if (! empty($propertyMappings)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $propertyMappings = array_replace_recursive($currentMappings, $propertyMappings);
            Factory::toFile($variantConfigFile, $propertyMappings);

            $this->log->debug(sprintf("Exported %s variant mappings to Config\\%s.",
                count($propertyMappings),
                basename($variantConfigFile)
            ));
        }
    }

    public function getMappedProperties()
    {
        return $this->getProperties($this->mappedProperties);
    }

    public function getProperties(array $properties)
    {
        return $this->getPropertiesQuery($properties, $this->dql[__FUNCTION__])->getResult();
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

    public function getPropertiesById(array $properties, array $id) {
        return $this->getPropertiesQuery($properties, $this->dql[__FUNCTION__])
            ->setParameter('id', $id)
            ->getResult();
    }
}
