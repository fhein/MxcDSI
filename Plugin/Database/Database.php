<?php

namespace MxcDropshipInnocigs\Plugin\Database;

use Doctrine\ORM\Tools\SchemaTool;
use MxcDropshipInnocigs\Plugin\ActionListener;
use MxcDropshipInnocigs\Plugin\Service\Logger;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use Zend\EventManager\EventInterface;

class Database extends ActionListener
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

    private $attributes;

    // unique MxcDropShipInnocigs attribute name prefix
    const ATTR_PREFIX = 'mxc_ds_inno_';

    /**
     * @param ModelManager $modelManager
     * @param CrudService $attributeService
     * @param Config $config
     * @param Logger $log
     */
    public function __construct(ModelManager $modelManager, CrudService $attributeService, Config $config, Logger $log)
    {
        parent::__construct($config);
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
//        foreach ($this->attributes as $table => $attributes) {
//            foreach ($attributes as $name => $type) {
//                try {
//                    $this->attributeService->update($table, self::ATTR_PREFIX . $name, $type);
//                    // $this->log->log('Added attribute '. self::ATTR_PREFIX . $name. '(type: ' . $type. ') to table '.$table);
//                } catch (Exception $e) {
//                    throw new DatabaseException('Attribute service failed to create attributes: ' . $e->getMessage());
//                }
//            }
//        }
//        $this->updateModel();
    }

    /**
     * Delete the attributes defined in the §attributes member from the database schema
     */
    private function deleteAttributes() {
//        foreach ($this->attributes as $table => $attributes) {
//            foreach ($attributes as $name => $type) {
//                try {
//                    $this->attributeService->delete($table, self::ATTR_PREFIX . $name);
//                    // $this->log->log('Deleted attribute '. self::ATTR_PREFIX . $name. '(type: ' . $type. ') from table '.$table);
//                } catch (Exception $e) {
//                    throw new DatabaseException('Attribute service failed to delete attributes: ' . $e->getMessage());
//                }
//            }
//        }
//        $this->updateModel();
//        return true;
    }

    private function updateModel()
    {
        $this->metaDataCache->deleteAll();
        $this->modelManager->generateAttributeModels(array_keys($this->attributes));
    }

    /**
     * Adds attributes and tables to the database schema
     * @param EventInterface $e
     * @return bool
     */
    public function onInstall(EventInterface $e)
    {
        $this->log->enter();
        $options = $this->getOptions();

        if (true === $options->createAttributes) {
            $this->createAttributes();
        }

        if (true === $options->createSchema) {
            $this->schemaTool->updateSchema(
                $this->getClassesMetaData(),
                true // make sure to use the save mode
            );
        }
        $this->log->leave();
        return true;
    }

    /**
     * Removes attributes and tables from the database schema
     * @param EventInterface $e
     * @return bool
     */
    public function onUninstall(EventInterface $e)
    {
        $this->log->enter();
        $options = $this->getOptions();
        if (true === $options->dropSchema) {
            $this->schemaTool->dropSchema(
                $this->getClassesMetaData()
            );
        }

        if (true === $options->deleteAttributes) {
            $this->deleteAttributes();
        }

        $this->log->leave();
        return true;
    }

    /**
     * Return Doctrine ORM class meta data, these classes define database tables
     *
     * @return array
     */
    private function getClassesMetaData()
    {
        $metaData = [];
        $models = $this->getOptions()->models;
        if (! $models) return $metaData;
        $models = $models->toArray();

        foreach ($models as $class) {
            $metaData[] = $this->modelManager->getClassMetadata($class);
         }
         return $metaData;
    }
}
