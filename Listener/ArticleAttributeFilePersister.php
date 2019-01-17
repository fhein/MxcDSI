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
    protected $articles;

    /**
     * @var string $articleConfigFile
     */
    protected $articleConfigFile = __DIR__ . '/../Config/article.config.php';

    protected $unusableCategories = [
        109 => 'Zubehör > Aspire Zubehör > alte Aspire Zubehör Kategorien > Aspire Plato ',
        110 => 'Zubehör > Aspire Zubehör > alte Aspire Zubehör Kategorien > Aspire Zubehör',
        126 => 'Zubehör > InnoCigs Zubehör > alte InnoCigs Zubehör Kategorien > InnoCigs eGo One',
        127 => 'Zubehör > InnoCigs Zubehör > alte InnoCigs Zubehör Kategorien > InnoCigs eGrip ',
        128 => 'Zubehör > InnoCigs Zubehör > alte InnoCigs Zubehör Kategorien > InnoCigs eRoll-C',
        129 => 'Zubehör > InnoCigs Zubehör > alte InnoCigs Zubehör Kategorien > InnoCigs eVic-VT',
    ];

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
        $config = [];

        foreach ($this->articles as $article) {
            $config[$article->getCode()] = [
                'name' => $article->getName(),
                'brand' => $article->getBrand(),
                'supplier' => $article->getSupplier(),
            ];
        }
        if (! empty($config)) {
            Factory::toFile($this->articleConfigFile, $config);
        }
        $this->log->debug('Brand/supplier information saved to ' . $this->articleConfigFile);
        $this->log->leave();
    }

    public function createListOfDefectArticles() {
        $config = [];
        foreach ($this->articles as $article) {
            $category = $article->getCategory();
            if ($category === null || $category === '') {
                $config['defects']['ic_api']['article']['category_missing'][$article->getCode()] = $article->getName();
            }
            foreach($this->unusableCategories as $uCategory) {
                if ($category === $uCategory) {
                    $config['defects']['ic_api']['article']['category_unusable'][$article->getCode()] =
                        [
                            'name' => $article->getName(),
                            'category' => $uCategory,
                        ];
                }
            }
        }
        Factory::toFile(__DIR__ . '/../Config/article.defects.php', $config);
    }

    public function createCategoryList() {
        $config = [];
        foreach($this->articles as $article)  {
            $config[$article->getCategory()] = true;
        }
        $tmp = array_keys($config);
        sort($tmp);
        $config = [
            'categories' => $tmp
        ];
        Factory::toFile(__DIR__ . '/../Config/categories.dump.php', $config);
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
        $this->articles = $this->modelManager->getRepository(InnocigsArticle::class)->findAll();
        $this->createArticleConfiguration();
        $this->createListOfDefectArticles();
        $this->createCategoryList();
    }
}