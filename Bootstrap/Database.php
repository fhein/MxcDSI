<?php

namespace MxcDropshipInnocigs\Bootstrap;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use MxcDropshipInnocigs\Helper\Log;
use MxcDropshipInnocigs\Models\InnocigsAttributeGroup;
use MxcDropshipInnocigs\Models\InnocigsAttribute;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;

class Database
{
    private $entityManager;
    private $modelManager;
    private $attributeService;
    private $metaDataCache;
    private $schemaTool;
    private $log;

    // unique MxcDropShipInnocigs attribute name prefix
    const ATTR_PREFIX = 'mxc_ds_inno_';

    // attributes to be added to shopware article
    // @TODO: this is where I stopped, currently these attributes are installed only, nothing more done with them
    private $attributes = [
        's_articles_attributes' => [
            'model' => 'string',
            'name'  => 'string',
            'purchase_price' => 'float',
            'retail_price' => 'float',
            'stock' => 'integer'
        ]
    ];

    /**
     * @param EntityManager $entityManager
     * @param ModelManager $modelManager
     * @param CrudService $attributeService
     */
    public function __construct(EntityManager $entityManager, ModelManager $modelManager, CrudService $attributeService)
    {
        // @TODO: How are symfony entity manager and shopware model manager related?

        // entityManager is the Doctrine EntityManager
        // modelManager is the Shopware ModelManager, they are related but obviously not the same
        // as it seems, both are needed

        $this->log = new Log();
        $this->log->log('Database');
        $this->entityManager = $entityManager;
        $this->modelManager = $modelManager;
        $this->metaDataCache = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        $this->schemaTool = new SchemaTool($this->entityManager);
        $this->attributeService = $attributeService;
    }

    /**
     * Add the attributes defined in the §attributes member to the database schema
     *
     * @return bool
     */
    private function createAttributes() {
        foreach ($this->attributes as $table => $attributes) {
            foreach ($attributes as $name => $type) {
                try {
                    $this->attributeService->update($table, self::ATTR_PREFIX . $name, $type);
                    // $this->log->log('Added attribute '. self::ATTR_PREFIX . $name. '(type: ' . $type. ') to table '.$table);
                } catch (Exception $e) {
                    return false;
                }
            }
        }
        $this->updateModel();
        return true;
    }

    /**
     * Delete the attributes defined in the §attributes member from the database schema
     *
     * @return bool
     */
    private function deleteAttributes() {
        foreach ($this->attributes as $table => $attributes) {
            foreach ($attributes as $name => $type) {
                try {
                    $this->attributeService->delete($table, self::ATTR_PREFIX . $name);
                    // $this->log->log('Deleted attribute '. self::ATTR_PREFIX . $name. '(type: ' . $type. ') from table '.$table);
                } catch (Exception $e) {
                    return false;
                }
            }
        }
        $this->updateModel();
        return true;
    }

    private function updateModel()
    {
        $this->metaDataCache->deleteAll();
        $this->modelManager->generateAttributeModels(array_keys($this->attributes));
    }

    /**
     * Adds attributes and tables to the database schema
     */
    public function install()
    {
        if ($this->createAttributes()) {
           $this->schemaTool->updateSchema(
                $this->getClassesMetaData(),
                true // make sure to use the save mode
            );
        }
    }

    /**
     * Removes attributes and tables from the database schema
     */
    public function uninstall()
    {
        $this->schemaTool->dropSchema(
            $this->getClassesMetaData()
        );
        return $this->deleteAttributes();
    }

    /**
     * Return Doctrine ORM class meta data, these classes define database tables
     *
     * @return array
     */
    private function getClassesMetaData()
    {
        return [
            $this->entityManager->getClassMetadata(InnocigsArticle::class),
            $this->entityManager->getClassMetadata(InnocigsVariant::class),
            $this->entityManager->getClassMetadata(InnocigsAttributeGroup::class),
            $this->entityManager->getClassMetadata(InnocigsAttribute::class),
        ];
    }
}
