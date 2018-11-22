<?php

namespace MxcDropshipInnocigs;

require __DIR__ . '/vendor/autoload.php';

use Exception;
use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Bootstrap\Database;
use MxcDropshipInnocigs\Client\InnocigsClient;
use MxcDropshipInnocigs\Convenience\ExceptionLogger;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Zend\Log\Logger;
use Zend\ServiceManager\ServiceManager;

class MxcDropshipInnocigs extends Plugin
{
    /**
     * @var ServiceManager $services
     */
    protected $services;
    /**
     * @var Logger $log
     */
    protected $log;
    /**
     * @var ExceptionLogger $exceptionLog
     */
    protected $exceptionLog;

    /*
     *  The parent constructor is marked final for whatever reason, puh
     */
    public function construct() {
        $this->services = Application::getServices();
        $this->log = $this->services->get('logger');
        $this->exceptionLog = $this->services->get('exceptionLogger');
    }

    /**
     * @param InstallContext $context
     * @return boolean
     */
    public function install(InstallContext $context)
    {
        $this->construct();
        $this->logAction();
        $options = $this->getOptions();
        if ($options->createSchema) {
            try {
                $database = $this->services->get(Database::class);
                $database->install();
            } catch (Exception $e) {
                $this->exceptionLog->log($e);
                $this->logAction(false);
                return false;
            }
        }
        $this->logAction(false);
        return true;
    }

    /**
     * @param UninstallContext $context
     * @return boolean
     */
    public function uninstall(UninstallContext $context)
    {
        $this->construct();
        $this->logAction();
        $options = $this->getOptions();
        if ($options->dropSchema) {
            try {
                $database = $this->services->get(Database::class);
                $database->uninstall();
            } catch (Exception $e) {
                $this->exceptionLog->log($e);
                $this->logAction(false);
                return false;
            }
        }
        $this->logAction(false);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context)
    {
        $this->construct();
        $this->logAction();
        $client = null;
        try {
            $options = $this->getOptions();
            if ($options->importArticles) {
                $client = $this->getInnocigsClient();
                $client->importArticles($options->onActivate->numberOfArticles ?? -1);
            }
            if ($options->saveArticleConfiguration){
                $client = $this->getInnocigsClient();
                $client->createArticleConfigurationFile($this->getPath());
            }
            if ($options->clearCache) {
                $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
            }
        } catch(Exception $e) {
            $this->exceptionLog->log($e);
        } finally {
            $this->logAction(false);
        }
    }

    public function deactivate(DeactivateContext $context)
    {
        $this->construct();
        $this->logAction();
        $options = $this->getOptions();
        if ($options->dropArticles) {
            // @todo: Drop Articles
            if ($options->dropConfigurator) {
                // @todo: Drop Groups and Options also
            }
        }
        $this->logAction(false);
    }

    protected function getInnocigsClient() {
        $this->client = $this->client ?? $this->services->get(InnocigsClient::class);
        return $this->client;
    }

    protected function getOptions() {
        $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        return $this->services->get('installOptions')->$function;
    }

    protected function logAction(bool $start = true) {
        $marker = '***********************';
        $text = $start ? 'START: ' : 'STOP: ';
        $text .= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $this->log->info(sprintf('%s %s %s', $marker, $text, $marker));

    }
}
