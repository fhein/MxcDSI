<?php

namespace MxcDropshipInnocigs\Models;

use Zend\Config\Factory;

class ArticleMappingRepository extends BaseEntityRepository
{
    protected $dql = [
        'exportMappings'  => 'SELECT :properties FROM MxcDropshipInnocigs\Models\ArticleMapping a INDEX BY a.icNumber',
    ];

    public function importMappings(string $articleConfigFile)
    {
        $mappings = [];

        if ($this->count() === 0 && file_exists($articleConfigFile)) {
            /** @noinspection PhpIncludeInspection */
            $mappings = include $articleConfigFile;
        }

        if (empty($mappings)) return;

        $em = $this->getEntityManager();
        foreach ($mappings as $mapping) {
            $articleMapping = new ArticleMapping();
            $articleMapping->fromArray($mapping);
            $em->persist($articleMapping);
        }
        $this->log->debug(sprintf("Imported %s article mappings from \\%s."
            , count($mappings),
            basename($articleConfigFile)
        ));
        /** @noinspection PhpUnhandledExceptionInspection */
        $em->flush();
    }

    public function exportMappings(string $articleConfigFile)
    {
        $articleMapping = new ArticleMapping();
        $properties = $articleMapping->getPrivatePropertyNames();

        $properties = array_map(function($property) { return 'a.' . $property; }, $properties);
        $properties = implode(', ', $properties);

        $dql = $this->dql[__FUNCTION__];
        $dql = str_replace(':properties', $properties, $dql);
        $propertyMappings = $this->getEntityManager()
            ->createQuery($dql)
            ->getResult();
        if (! empty($propertyMappings)) {
            /** @noinspection PhpUndefinedFieldInspection */
            Factory::toFile($articleConfigFile, $propertyMappings);

            $this->log->debug(sprintf("Exported %s article mappings to Config\\%s.",
                count($propertyMappings),
                basename($articleConfigFile)
            ));
        }
    }
}
