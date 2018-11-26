<?php

namespace MxcDropshipInnocigs\Plugin;

use Zend\Config\Config;
use Zend\EventManager\EventInterface;

abstract class ActionListener {

    /**
     * @var Config $config
     */
    protected $config;

    public function __construct(Config $config) {
        $class = get_class($this);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->config = $config->plugin->$class;
    }

    protected function getOptions() {
        $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $options = $this->config->$function ?? new Config([]);;
        $general = $this->config->general ?? new Config([]);
        $options->merge($general);
        return $options;
    }

    public function onInstall(EventInterface $e) {}
    public function onUninstall(EventInterface $e) {}
    public function onActivate(EventInterface $e) {}
    public function onDeactivate(EventInterface $e) {}
}