<?php

namespace MxcDropshipInnocigs\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use Doctrine\ORM\Events;
use Zend\EventManager\EventManager;
use Zend\Log\Logger;

class InnocigsArticleSubscriber implements EventSubscriber
{
    /**
     * @var Logger $log
     */
    private $log;

    /**
     * @var  EventManager $events
     */
    private $events;

    public function __construct() {
        // @todo: Code smell: Constructed via Shopware's ServiceManager, so we connect to our service management via global Application
        $services = Application::getServices();
        $this->log = $services->get(Logger::class);
        $this->events = $services->get('events');
    }

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
     * @param PreUpdateEventArgs $arguments
     */
    public function preUpdate(PreUpdateEventArgs $arguments)
    {
        /** @var EntityManager $modelManager */
        //$modelManager = $arguments->getEntityManager();

        $article = $arguments->getEntity();

        if (! $article instanceof InnocigsArticle) {
            return;
        }
        if ($arguments->hasChangedField('active')) {
            // $this->log->info('preUpdate: ' . $article->getName() . ' has changed state to ' . ($article->isActive() ? 'true' : 'false'));
            $params = compact('article');
            $this->events->trigger('article_active_state_changed', $this, $params);
        } else {
            $this->log->info('preUpdate: ' . $article->getName() . ' has no state change.');
        }
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
