<?php

namespace MxcDropshipInnocigs\Bootstrap;

use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use MxcDropshipInnocigs\Exception\DatabaseException;
use MxcDropshipInnocigs\Models\InnocigsAttributeGroup;
use MxcDropshipInnocigs\Models\InnocigsAttribute;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Zend\Log\Logger;

class Database
{
    /**
     * @var ModelManager $modelManager
     */
    private $modelManager;

    /**
     * @var CrudService $attributeService
     */
    private $attributeService;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider $metaDataCache
     */
    private $metaDataCache;

    /**
     * @var SchemaTool $schemaTool
     */
    private $schemaTool;

    /**
     * @var Logger $log
     */
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
     * @param ModelManager $modelManager
     * @param CrudService $attributeService
     * @param Logger $log
     */
    public function __construct(ModelManager $modelManager, CrudService $attributeService, Logger $log)
    {
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->metaDataCache = $this->modelManager->getConfiguration()->getMetadataCacheImpl();
        $this->schemaTool = new SchemaTool($this->modelManager);
        $this->attributeService = $attributeService;
    }

    /**
     * Add the attributes defined in the §attributes member to the database schema
     */
    private function createAttributes() {
        foreach ($this->attributes as $table => $attributes) {
            foreach ($attributes as $name => $type) {
                try {
                    $this->attributeService->update($table, self::ATTR_PREFIX . $name, $type);
                    // $this->log->log('Added attribute '. self::ATTR_PREFIX . $name. '(type: ' . $type. ') to table '.$table);
                } catch (Exception $e) {
                    throw new DatabaseException('Attribute service failed to create attributes: ' . $e->getMessage());
                }
            }
        }
        $this->updateModel();
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
                    throw new DatabaseException('Attribute service failed to delete attributes: ' . $e->getMessage());
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
        $this->createAttributes();
        $this->schemaTool->updateSchema(
            $this->getClassesMetaData(),
            true // make sure to use the save mode
        );
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
            $this->modelManager->getClassMetadata(InnocigsArticle::class),
            $this->modelManager->getClassMetadata(InnocigsVariant::class),
            $this->modelManager->getClassMetadata(InnocigsAttributeGroup::class),
            $this->modelManager->getClassMetadata(InnocigsAttribute::class),
        ];
    }
}
