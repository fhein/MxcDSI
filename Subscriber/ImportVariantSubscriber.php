<?php

namespace MxcDropshipInnocigs\Subscriber;

use Mxc\Shopware\Plugin\Subscriber\EntitySubscriber;
use Zend\EventManager\EventInterface;

class ImportVariantSubscriber extends EntitySubscriber
{
    public function preUpdate(EventInterface $e)
    {
    }

    public function prePersist(EventInterface $e)
    {
    }
}
