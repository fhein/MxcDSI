<?php

namespace MxcDropshipInnocigs\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use Doctrine\ORM\Events;
use Zend\Log\Logger;

class InnocigsArticleSubscriber implements EventSubscriber
{
    private $log;

    public function __construct() {
        $this->log = Application::getServices()->get(Logger::class);
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
            $this->log->info('preUpdate: ' . $article->getName() . 'has changed state to ' . ($article->isActive() ? 'true' : 'false'));
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
