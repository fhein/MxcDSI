<?php

namespace MxcDropshipInnocigs\Plugin;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;

abstract class ActionListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * @var ContainerInterface $services
     */
    protected $services;

    abstract public function onInstall(EventInterface $e);
    abstract public function onUninstall(EventInterface $e);
    abstract public function onActivate(EventInterface $e);
    abstract public function onDeactivate(EventInterface $e);

    public function __construct(ContainerInterface $services) {
        $this->services = $services;
    }

    protected function getOptions() {
        $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $class = get_class($this);
        return $this->services->get('config')->plugin->listeners->$class->$function?? new Config();
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('install', [$this, 'onInstall'], $priority);
        $this->listeners[] = $events->attach('activate', [$this, 'onActivate'], $priority);
        $this->listeners[] = $events->attach('deactivate', [$this, 'onDeactivate'], $priority);
        $this->listeners[] = $events->attach('uninstall', [$this, 'onUninstall'], $priority);
    }
}