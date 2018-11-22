<?php

namespace MxcDropshipInnocigs;

require __DIR__ . '/vendor/autoload.php';

use Exception;
use MxcDropshipInnocigs\Application\Application;
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
     * @param InstallContext $context
     * @return boolean
     */
    public function install(InstallContext $context)
    {
        $services = Application::getServices();
        $options = $this->getOptions();
        if ($options->createSchema) {
            $exceptionLogger = $services->get('exceptionLogger');
            try {
                $database = $services->get(Database::class);
                $database->install();
            } catch (Exception $e) {
                $exceptionLogger->log($e);
                return false;
            }
        }
        return true;
    }

    /**
     * @param UninstallContext $context
     * @return boolean
     */
    public function uninstall(UninstallContext $context)
    {
        $services = Application::getServices();
        $options = $this->getOptions();
        if ($options->dropSchema) {
            $exceptionLogger = $services->get('exceptionLogger');
            try {
                $database = $services->get(Database::class);
                $database->uninstall();
            } catch (Exception $e) {
                $exceptionLogger->log($e);
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context)
    {
        $services = Application::getServices();
        $client = $services->get(InnocigsClient::class);
        $exceptionLogger = $services->get('exceptionLogger');
        try {
            $options = $this->getOptions();
            if ($options->importItems) {
                $client->importArticles($options->onActivate->numberOfArticles ?? -1);
            }
            if ($options->saveArticleConfiguration){
                $client->createArticleConfigurationFile($this->getPath());
            }
            if ($options->clearCache) {
                $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
            }
        } catch(Exception $e) {
            $exceptionLogger->log($e);
        }
    }

    public function deactivate(DeactivateContext $context)
    {
        $options = $this->getOptions();
        if ($options->dropArticles) {
            // @todo: Drop Articles
            if ($options->dropConfigurator) {
                // @todo: Drop Groups and Options also
            }
        }
    }

    protected function getOptions() {
        $services = Application::getServices();
        $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        return $services->get('installOptions')->$function;
    }
}
