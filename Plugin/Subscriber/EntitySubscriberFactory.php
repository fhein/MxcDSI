<?php

namespace MxcDropshipInnocigs\Plugin\Subscriber;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class EntitySubscriberFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /**
         * @var EntitySubscriber $subscriber
         */
        $logger = $container->get('logger');
        $subscriber = new $requestedName($logger);
        $config = $container->get('config')->model_subscribers->$requestedName;
        $events = $container->get(ModelSubscriber::class)->getEventManager();
        $model = $config->model;
        foreach($config->events as $event) {
            $subscriber->attach($events, $model, $event);
        }
        return $subscriber;
    }
}