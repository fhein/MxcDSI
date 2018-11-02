<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 02.11.2018
 * Time: 18:39
 */

namespace MxcDropshipInnocigs\Application;

use Doctrine\DBAL\Connection;
use MxcDropshipInnocigs\Bootstrap\Database;
use MxcDropshipInnocigs\Bootstrap\DatabaseFactory;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Client\ApiClientFactory;
use MxcDropshipInnocigs\Client\Credentials;
use MxcDropshipInnocigs\Client\CredentialsFactory;
use MxcDropshipInnocigs\Client\InnocigsClient;
use MxcDropshipInnocigs\Client\InnocigsClientFactory;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware_Components_Config;
use Zend\Log\Logger;
use Zend\ServiceManager\ServiceManager;

class Application {
    /**
     * @var ServiceManager $services
     */
    private static $services;

    private static $servicesConfig = [
        'factories' => [
            ApiClient::class                    => ApiClientFactory::class,
            Credentials::class                  => CredentialsFactory::class,
            InnocigsClient::class               => InnocigsClientFactory::class,
            Database::class                     => DatabaseFactory::class,
            Logger::class                       => LoggerFactory::class,
            ModelManager::class                 => ModelManagerFactory::class,
            Connection::class                   => DbalConnectionFactory::class,
            Shopware_Components_Config::class   => ConfigurationFactory::class,
            CrudService::class                  => AttributeManagerFactory::class,
        ],
        'aliases' => [
            'config'            => Shopware_Components_Config::class,
            'dbalConnection'    => Connection::class,
            'logger'            => Logger::class,
            'modelManager'      => ModelManager::class,
            'attributeManager'  => CrudService::class,
        ]
    ];

    /**
     * @return ServiceManager
     */
    public static function getServices() {
        if (self::$services) return self::$services;
        self::$services = new ServiceManager(self::$servicesConfig);
        return self::$services;
    }
}