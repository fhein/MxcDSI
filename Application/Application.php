<?php

namespace MxcDropshipInnocigs\Application;

use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\EventManager\EventManager;
use Zend\ServiceManager\ServiceManager;


class Application {
    /**
     * @var ServiceManager $services
     */
    private static $services;

    /**
     * @return ServiceManager
     */
    public static function getServices() {
        if (self::$services) return self::$services;
        $config = Factory::fromFile(__DIR__ . '/../config.php');
        self::$services = new ServiceManager($config['services']);
        self::$services->setService(Config::class, new Config($config));
        self::$services->setService('events', new EventManager());
        return self::$services;
    }
}
