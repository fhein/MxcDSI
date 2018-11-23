<?php

namespace MxcDropshipInnocigs\Plugin;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Plugin\Application\AbstractActionListener;
use MxcDropshipInnocigs\Plugin\Service\LoggerDelegatorFactory;
use MxcDropshipInnocigs\Plugin\Shopware\AttributeManagerFactory;
use MxcDropshipInnocigs\Plugin\Shopware\ConfigurationFactory;
use MxcDropshipInnocigs\Plugin\Shopware\DbalConnectionFactory;
use MxcDropshipInnocigs\Plugin\Shopware\MediaServiceFactory;
use MxcDropshipInnocigs\Plugin\Shopware\ModelManagerFactory;
use Shopware\Components\Plugin;
use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Log\Logger;
use Zend\Log\LoggerServiceFactory;
use Zend\ServiceManager\ServiceManager;

abstract class Application extends AbstractActionListener {

    private static $serviceConfig = [
        'factories' => [
            // shopware service interface
            'dbalConnection'    => DbalConnectionFactory::class,
            'attributes'        => AttributeManagerFactory::class,
            'mediaManager'      => MediaServiceFactory::class,
            'modelManager'      => ModelManagerFactory::class,
            'pluginConfig'      => ConfigurationFactory::class,

            // services
            Logger::class       => LoggerServiceFactory::class,

        ],
        'delegators' => [
            Logger::class => [
                LoggerDelegatorFactory::class,
            ],
        ],
        'aliases' => [
            'logger' => Logger::class,
        ]
    ];
    /**
     * @var ServiceManager $services
     */
    protected static $globalServices;

    /**
     * @var LoggerInterface $log;
     */
    protected $log;

    /**
     * @var EventManagerInterface $events
     */
    protected $events;

    /**
     * @var ContainerInterface $services
     */
    protected $services;

    /**
     * @var array $pluginActionListeners
     */
    protected static $pluginActionListeners;

    public static function getEvents() {
        return self::getServices()->get('events');
    }

    /**
     * @param Plugin $plugin
     * @return null|EventManagerInterface
     */
    public static function fromPlugin(Plugin $plugin) {
        $services = self::getServices();

        $log = $services->get('logger');

        $pluginOptions = $services->get('config')->plugin;
        if (! $pluginOptions instanceof Config) {
            $log->emerg('installOptions not Config.');
            return null;
        }
        $appConfig = $pluginOptions->application;
        if (! ($appConfig instanceof Config))  {
            $log->emerg('installOptions->application not Config.');
            return null;
        }
        if (count($appConfig) !== 1) {
            $log->emerg('Number of registered applications not equal to 1.');
            return null;

        }
        $appClass = array_keys($appConfig->toArray())[0];
        if (! class_exists($appClass)) {
            $log->emerg(sprintf('Application class "%s" does not exist.', $appClass));
            return null;
        }
        $app = new $appClass($services);

        $listenerConfig = $pluginOptions->listeners;
        if (null !== $listenerConfig && ! ($listenerConfig instanceof Config)) {
            // @todo: error handling ($listener config available but no config)
            return null;

        }
        $listeners = array_keys($listenerConfig->toArray());
        foreach ($listeners as $class) {
            if (! class_exists($class)) {
                $log->emerg(sprintf('Plugin listener class "%s" does not exist.', $class));
                return null;
            }
            self::$pluginActionListeners[] = new $class($services);
        }
        $services->setAllowOverride(true);
        $services->setService('application', $app);
        $services->setService('plugin', $plugin);
        $services->setAllowOverride(false);

        /**
         * @var Application $app
         */
        return $app->getEventManager();
    }

    public static function fromController(ContainerInterface $services, EventManagerInterface $events) {
    }

    /**
     * @return ServiceManager
     */
    public static function getServices() {
        if (self::$globalServices) return self::$globalServices;
        $services = new ServiceManager(self::$serviceConfig);
        $config = Factory::fromFile(__DIR__ . '/../Config/application.config.php');
        $services->setAllowOverride(true);
        $services->configure($config['services']);
        $services->setAllowOverride(false);
        $services->setService('config', new Config($config));
        $services->setService('events', new EventManager());
        self::$globalServices = $services;
        return self::$globalServices;
    }

    public function __construct(ContainerInterface $services, EventManagerInterface $events = null) {
        parent::__construct($services);
        $events = $events ?? ($services->has('events') ? $services->get('events') : new EventManager());
        $this->events = $events ?? $services->has('events') ?? new EventManager();
        $this->log = $services->get('logger');
        $this->attach($events);
    }

    public function getEventManager() {
        if (! $this->events) {
            $this->events = new EventManager();
        }
        return $this->events;
    }

    protected function getOptions() {
        $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $class = get_class($this);
        return $this->services->get('config')->plugin->application->$class->$function ?? new Config();
    }
}
