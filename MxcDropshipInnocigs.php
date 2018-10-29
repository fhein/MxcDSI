<?php

namespace MxcDropshipInnocigs;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use MxcDropshipInnocigs\Bootstrap\Database;
use MxcDropshipInnocigs\Client\InnocigsClient;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class MxcDropshipInnocigs extends Plugin
{
    /**
     * @param InstallContext $installContext
     * @return boolean
     */
    public function install(InstallContext $installContext)
    {
        $entityManager = $this->container->get('models');
        $database = new Database(
            $entityManager,
            Shopware()->Models(),
            $this->container->get('shopware_attribute.crud_service')
        );
        $database->install();
        return true;
    }


    /**
     * @param UninstallContext $uninstallContext
     * @return boolean
     */
    public function uninstall(UninstallContext $uninstallContext)
    {
        if ($uninstallContext->keepUserData()) {
            return true;
        }

        $database = new Database(
            $this->container->get('models'),
            Shopware()->Models(),
            $this->container->get('shopware_attribute.crud_service')
        );

        return $database->uninstall();
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext)
    {
        // download InnoCigs items
        $entityManager = $this->container->get('models');
        $config = $this->container->get('config');
        $user = $config->offsetGet('api_user');
        $password = $config->offsetGet('api_password');
        $client = new InnocigsClient($entityManager, $user, $password);
        $result = $client->downloadItems();
        $activateContext->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        return $result;
    }

    public function deactivate(DeactivateContext $context)
    {
        parent::deactivate($context); // TODO: Change the autogenerated stub
    }
}
