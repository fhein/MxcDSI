<?php

namespace MxcDropshipInnocigs\Subscriber;

use Mxc\Shopware\Plugin\Subscriber\EntitySubscriber;
use Zend\EventManager\EventInterface;

class ImportArticleSubscriber extends EntitySubscriber
{
    public function preUpdate(EventInterface $e)
    {
    }

    public function prePersist(EventInterface $e)
    {
    }
}