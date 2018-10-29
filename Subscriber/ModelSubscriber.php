<?php

namespace MxcDropshipInnocigs\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Doctrine\ORM\Events;

class ModelSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
           Events::preUpdate,
           Events::postUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs $arguments
     */
    public function preUpdate(LifecycleEventArgs $arguments)
    {
        /** @var EntityManager $modelManager */
        //$modelManager = $arguments->getEntityManager();

        $model = $arguments->getEntity();

        if (! $model instanceof InnocigsVariant) {
            return;
        }

        // modify item data
    }

    /**
     * @param LifecycleEventArgs $arguments
     */
    public function postUpdate(LifecycleEventArgs $arguments)
    {
        /** @var EntityManager $modelManager */
        //$modelManager = $arguments->getEntityManager();

        // $model = $arguments->getEntity();

        // modify models or do some other fancy stuff
    }
}
