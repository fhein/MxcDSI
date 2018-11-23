<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 09.11.2018
 * Time: 19:20
 */

namespace MxcDropshipInnocigs\Convenience;

use Doctrine\ORM\OptimisticLockException;
use Exception;
use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Exception\DatabaseException;
use Shopware\Components\Model\ModelEntity;
use Shopware\Components\Model\ModelManager;

trait ModelManagerTrait
{
    /**
     * @var ModelManager $modelManager
     *
     */
    private $modelManager;

    private function persist(ModelEntity $entity) {
        $this->getModelManager()->persist($entity);
    }

    /**
     * Flush the changes to the Doctrine model mapping an Doctrine exception
     * to our DatabaseException.
     *
     * @throws DatabaseException
     */
    public function flush() {
        try {
            $this->getModelManager()->flush();
        } catch (OptimisticLockException $e) {
            throw new DatabaseException($e->getMessage());
        } catch (Exception $e) {
            Application::getServices()->get('logger')->except($e);
        }
    }

    private function getRepository(string $name) {
        return $this->getModelManager()->getRepository($name);
    }

    private function createQuery(string $dql) {
        return $this->getModelManager()->createQuery($dql);
    }

    private function getModelManager() {
        if (! $this->modelManager) {
            $this->modelManager = Application::getServices()->get('modelManager');
        }
        return $this->modelManager;
    }
}