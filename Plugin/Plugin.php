<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 23.11.2018
 * Time: 20:41
 */

namespace MxcDropshipInnocigs\Plugin;

use MxcDropshipInnocigs\Plugin\Service\LoggerDelegatorFactory;
use MxcDropshipInnocigs\Plugin\Shopware\AttributeManagerFactory;
use MxcDropshipInnocigs\Plugin\Shopware\ConfigurationFactory;
use MxcDropshipInnocigs\Plugin\Shopware\DbalConnectionFactory;
use MxcDropshipInnocigs\Plugin\Shopware\MediaServiceFactory;
use MxcDropshipInnocigs\Plugin\Shopware\ModelManagerFactory;
use Shopware\Components\Plugin as Base;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\EventManager\EventManager;
use Zend\Log\Logger;
use Zend\Log\LoggerServiceFactory;
use Zend\ServiceManager\ServiceManager;

class Plugin extends Base
{
    /**
     * @var ServiceManager $services
     */
    protected static $globalServices;

    /**
     * @var array $pluginActionListeners
     */
    protected static $pluginActionListeners;

    private static $serviceConfig = [
        'factories' => [
            // shopware service interface
            'dbalConnection'    => DbalConnectionFactory::class,
            'attributeManager'  => AttributeManagerFactory::class,
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
     * @return ServiceManager
     */
    public static function getServices() {
        if (self::$globalServices) return self::$globalServices;
        $services = new ServiceManager(self::$serviceConfig);
        $config = Factory::fromFile(__DIR__ . '/../Config/application.config.php');
        $services->setAllowOverride(true);
        $services->configure($config['services']);
        $services->setService('config', new Config($config));
        $services->setService('events', new EventManager());
        $services->setAllowOverride(false);
        self::$globalServices = $services;
        return self::$globalServices;
    }

    /**
     * @param Plugin $plugin
     * @param string $function
     * @param $param
     */
    public static function trigger(Plugin $plugin, string $function, $param) {
        $services = self::getServices();
        $services->setAllowOverride(true);
        $services->setService('plugin', $plugin);
        $services->setAllowOverride(false);
        $listenerConfig = $services->get('config')->plugin;
        $events = new Events($services, $listenerConfig);
        $events = $events->attach($function);
        $events->triggerUntil(
            function ($result) {
                return $result === false;
            },
            $function,
            ['context' => $param]
        );
    }

    protected function pluginAction(string $function, $context) {
        try{
            self::trigger($this, $function, $context);
        } catch (Throwable $e) {
            self::getServices()->get('logger')->except($e);
        }
    }

    public function install(InstallContext $context)
    {
        $this->pluginAction(__FUNCTION__, $context);
    }

    public function uninstall(UninstallContext $context)
    {
        $this->pluginAction(__FUNCTION__, $context);
    }

    public function activate(ActivateContext $context)
    {
        $this->pluginAction(__FUNCTION__, $context);
    }

    public function deactivate(DeactivateContext $context)
    {
        $this->pluginAction(__FUNCTION__, $context);
    }
}
