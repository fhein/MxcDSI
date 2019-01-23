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
use MxcDropshipInnocigs\Models\Current\Article;
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

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e)
    {
        $this->log->enter();
        $r = $this->modelManager->getRepository(Article::class);
        /** @noinspection PhpUndefinedFieldInspection */
        $config = $r->getSupplierBrand($this->config->innocigsBrands->toArray());
        /** @noinspection PhpUndefinedFieldInspection */
        Factory::toFile($this->config->articleConfigFile, $config);
        $this->log->leave();
    }
}