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
use MxcDropshipInnocigs\Models\Article;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\EventManager\EventInterface;

class DumpOnUninstall extends ActionListener
{
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;
    protected $articles;

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

    public function createListOfDefectArticles() {
        $config = [];
        foreach ($this->articles as $article) {
            /** @var Article $article */
            $category = $article->getCategory();
            if ($category === null || $category === '') {
                $config['defects']['ic_api']['article']['category_missing'][$article->getNumber()] = $article->getName();
            }
            foreach($this->unusableCategories as $uCategory) {
                if ($category === $uCategory) {
                    $config['defects']['ic_api']['article']['category_unusable'][$article->getNumber()] =
                        [
                            'name' => $article->getName(),
                            'category' => $uCategory,
                        ];
                }
            }
            if ($article->getManufacturer() === 'Smok') {
                $config['defects']['ic_api']['article']['manufacturer_wrong'][$article->getNumber()] = [
                    'name' => $article->getName(),
                    'manufacturer_from_api' => $article->getManufacturer(),
                    'manufacturer_correct' => $article->getBrand(),
                ];
            }
            if ($article->getManufacturer() !== $article->getBrand()) {
                $config['defects']['ic_api']['article']['manufacturer_different'][$article->getNumber()] = [
                    'name' => $article->getName(),
                    'manufacturer' => $article->getManufacturer(),
                    'brand' => $article->getBrand(),
                ];
            }
        }
        Factory::toFile(__DIR__ . '/../Dump/article.defects.php', $config);
    }

    public function createCategoryList() {
        $config = [];
        foreach($this->articles as $article)  {
            /** @var Article $article */
            $config[$article->getCategory()] = true;
        }
        $tmp = array_keys($config);
        sort($tmp);
        $config = [
            'categories' => $tmp
        ];
        Factory::toFile(__DIR__ . '/../Dump/categories.dump.php', $config);
    }

    public function dumpInnocigsBrandsAndAkkus() {
        $r = $this->modelManager->getRepository(Article::class);
        $akkus = $r->createQueryBuilder('a')
            ->select('a.number')
            ->where('a.manufacturer = \'Akkus\'')
            ->getQuery()
            ->getScalarResult();
        $config['akkus'] = array_column($akkus, 'code');;
        $innocigs = $r->createQueryBuilder('a')
            ->select('a.number')
            ->where('a.manufacturer IN (\'InnoCigs\', \'Steamax\', \'SC\')')
            ->getQuery()
            ->getScalarResult();
        $config['innocigs'] = array_column($innocigs, 'code');
        Factory::toFile(__DIR__ . '/../Dump/ia.dump.php', $config);
    }

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e)
    {
        $this->articles = $this->modelManager->getRepository(Article::class)->findAll();
        $this->createListOfDefectArticles();
        $this->dumpInnocigsBrandsAndAkkus();
        $this->createCategoryList();
    }
}