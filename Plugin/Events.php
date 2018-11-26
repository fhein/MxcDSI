<?php

namespace MxcDropshipInnocigs\Plugin;


use Interop\Container\ContainerInterface;
use Zend\Config\Config;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

class Events
{
    /**
     * @var ContainerInterface $services
     */
    protected $services;

    /**
     * @var EventManagerInterface $events
     */
    protected $events;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @var array $listeners
     */
    protected $listeners = [];

    public function __construct(ContainerInterface $services, Config $config, EventManagerInterface $events = null) {
        $this->services = $services;
        $this->events = $events ?? ($services->has('events') ? $services->get('events') : new EventManager());
        $this->config = $config ?? new Config([]);
    }

    public function attach(string $function) {
        $config = $this->config->toArray();
        $actionListeners = array_keys($config);
        $listeners = [];
        foreach ($actionListeners as $service) {
            $listeners[] = $this->services->get($service);
        }
        if ($function === 'uninstall' || $function === 'deactivate') {
            $listeners = array_reverse($listeners);
        }
        $handler = 'on' . ucfirst($function);
        foreach ($listeners as $listener) {
            $this->events->attach($function, [$listener, $handler]);
        }
        return $this->events;
    }
}