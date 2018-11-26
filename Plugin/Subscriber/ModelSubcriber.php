<?php

namespace MxcDropshipInnocigs\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Zend\EventManager\EventManagerInterface;

class ModelSubscriber implements EventSubscriber
{
    private $events;

    public function __construct(EventManagerInterface $events) {
        $this->events = $events;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::preRemove,
            Events::postRemove,
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::postLoad,
            Events::loadClassMetadata,
            Events::onClassMetadataNotFound,
            Events::preFlush,
            Events::onFlush,
            Events::postFlush,
            Events::onClear,
        ];
    }

    public function preRemove(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }
    public function postRemove(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }

    public function prePersist(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }

    public function postPersist(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->trigger(__FUNCTION__, $args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->trigger(__FUNCTION__, $args);
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $this->trigger(__FUNCTION__, $args);
    }

    public function loadClassMetaData(LifecycleEventArgs $args)
    {
        $this->trigger(__FUNCTION__, $args);
    }

    public function onClassMetaDataNotFound(LifecycleEventArgs $args)
    {
        $this->trigger(__FUNCTION__, $args);
    }

    public function preFlush(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }

    public function onFlush(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }

    public function postFlush(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }

    public function onClear(LifecycleEventArgs $args) {
        $this->trigger(__FUNCTION__, $args);
    }

    protected function trigger(string $event, $args) {
        $this->events->triggerUntil(
            function($result) {
                return $result === true;
            },
            $event,
            [ 'args' => $args ]
        );
    }
}
