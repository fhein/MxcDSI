<?php

namespace MxcDropshipInnocigs\Application;

use MxcDropshipInnocigs\Client\InnocigsClient;
use MxcDropshipInnocigs\Plugin\Application as Base;
use Zend\EventManager\EventInterface;

class Application extends Base {

    /**
     * @var InnocigsClient $client
     */
    protected $client;

    public function onInstall(EventInterface $e)
    {
        $this->log->enter();
        $options = $this->getOptions();
        if ($options->createSchema) {
            try {
                $database = $this->services->get(Database::class);
                $database->install();
            } catch (Throwable $e) {
                $this->log->except($e);
                $this->log->leave();
                return false;
            }
        }
        $this->log->leave();
        return true;
    }

    public function onUninstall(EventInterface $e)
    {
        $this->log->enter();
        $options = $this->getOptions();
        if ($options->dropSchema) {
            try {
                $database = $this->services->get(Database::class);
                $database->uninstall();
            } catch (Throwable $e) {
                $this->log->except($e);
                $this->log->leave();
                return false;
            }
        }
        $this->log->leave(false);
        return true;
    }

    public function onActivate(EventInterface $e)
    {
        $this->log->enter();
        $client = null;
        $context = $e->getParam('context');
        try {
            $options = $this->getOptions();
            if (true === $options->importArticles) {
                $client = $this->getInnocigsClient();
                $client->importArticles($options->onActivate->numberOfArticles ?? -1);
            }
            if (true === $options->saveArticleConfiguration){
                $client = $this->getInnocigsClient();
                $client->createArticleConfigurationFile($this->services->get('plugin')->getPath());
            }
            if (true === $options->clearCache) {
                $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
            }
        } catch(Exception $e) {
            $this->log->except($e);
        } finally {
            $this->log->leave();
        }
    }

    public function onDeactivate(EventInterface $e)
    {
        $this->log->enter();
        $options = $this->getOptions();
        if (true === $options->dropArticles) {
            // @todo: Drop Articles
            if (true === $options->dropConfigurator) {
                // @todo: Drop Groups and Options also
            }
        }
        $this->log->leave();
    }

    protected function getInnocigsClient() {
        $this->client = $this->client ?? $this->services->get(InnocigsClient::class);
        return $this->client;
    }
}
