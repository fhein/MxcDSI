<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 11.01.2019
 * Time: 14:52
 */

namespace MxcDropshipInnocigs\Listener;


use Mxc\Shopware\Plugin\ActionListener;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\EventManager\EventInterface;

class ArticleAttributeFilePersister extends ActionListener
{
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    /**
     * @var string $articleConfigFile
     */
    protected $articleConfigFile = __DIR__ . '/../Config/article.config.php';

    /**
     * ArticleAttributeFilePersister constructor.
     * @param ModelManager $modelManager
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(ModelManager $modelManager, Config $config, LoggerInterface $log)
    {
        parent::__construct($config, $log);
        $this->modelManager = $modelManager;
    }

    public function createArticleConfiguration()
    {
        $this->log->enter();
        $articles = $this->modelManager->getRepository(InnocigsArticle::class)->findAll();
        $config = [];

        foreach ($articles as $article) {
            $config[$article->getCode()] = [
                'name' => $article->getName(),
                'brand' => $article->getBrand(),
                'supplier' => $article->getSupplier(),
            ];
        }
        Factory::toFile($this->articleConfigFile, $config);
        $this->log->debug('Brand/supplier information saved to ' . $this->articleConfigFile);
        $this->log->leave();
    }

    public function installArticleConfiguration() {
        $this->log->enter();
        if (! file_exists($this->articleConfigFile)) {
            $distributedFile = $this->articleConfigFile . '.dist';
            if (file_exists($distributedFile)) {
                $this->log->debug('Creating brand/supplier file from plugin distribution.');
                copy($distributedFile, $this->articleConfigFile);
            } else {
                $this->log->debug(sprintf(
                    'Distributed brand/supplier %sfile missing. Nothing done.',
                    $distributedFile
                ));
            }
        } else {
            $this->log->debug(sprintf(
                'Brand/supplier file %s already present. Nothing done.',
                $this->articleConfigFile
            ));
        }
        $this->log->leave();
    }

    public function install(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e) {
        $this->installArticleConfiguration();
    }

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e)
    {
        $this->createArticleConfiguration();
    }
}