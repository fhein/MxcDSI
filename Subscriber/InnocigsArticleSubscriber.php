<?php

namespace MxcDropshipInnocigs\Subscriber;

use Mxc\Shopware\Plugin\Subscriber\EntitySubscriber;
use Zend\EventManager\EventInterface;

class InnocigsArticleSubscriber extends EntitySubscriber
{
    /**
     * @param EventInterface $e
     * @return bool
     */
    public function preUpdate(EventInterface $e)
    {
        $this->log->info(__CLASS__ . '#' . __FUNCTION__ . 'was triggered.');
        $arguments = $e->getParam('args');
        if ($arguments->hasChangedField('active')) {
            $params = ['article' => $arguments->getEntity()];
            $this->events->trigger('article_active_state_changed', $this, $params);
        }
        // false indicates that we do not want to abort event processing here
        return false;
    }
}
