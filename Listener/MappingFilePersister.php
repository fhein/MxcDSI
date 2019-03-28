<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace MxcDropshipInnocigs\Listener;


use Mxc\Shopware\Plugin\ActionListener;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\ArticleMapping;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use Zend\EventManager\EventInterface;

class MappingFilePersister extends ActionListener
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    protected $log;
    protected $config;

    /**
     * MappingFilePersister constructor.
     *
     * @param ModelManager $modelManager
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(ModelManager $modelManager, Config $config, LoggerInterface $log)
    {
        $this->log = $log;
        $this->config = $config;
        $this->modelManager = $modelManager;
    }

    public function install(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e)
    {
        $this->log->enter();
        $repository = $this->modelManager->getRepository(ArticleMapping::class);
        $repository->importMappings($this->config['articleConfigFile']);
        $this->log->leave();
    }

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e)
    {
        $this->log->enter();
        $repository = $this->modelManager->getRepository(ArticleMapping::class);
        $repository->exportMappings($this->config['articleConfigFile']);
        $this->log->leave();
    }
}