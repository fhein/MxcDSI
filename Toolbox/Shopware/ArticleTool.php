<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Repository;

class ArticleTool {

    /** @var LoggerInterface */
    protected $log;

    /** @var Repository */
    protected $articleRepository;

    /** @var ModelManager  */
    protected $modelManager;

    public function __construct(ModelManager $modelManager, LoggerInterface $log)
    {
        $this->modelManager = $modelManager;
        $this->log = $log;
    }

    public function setMainDetail(Detail $detail)
    {
        /** @var Article $article */
        $article = $detail->getArticle();
        if (! $article) return;

        $oldMainDetail = $article->getMainDetail();
        if ($oldMainDetail) {
            $oldMainDetail->setKind(2);
        }

        $article->setMainDetail($detail);
        $detail->setKind(1);
    }

    public function deleteDetail(Detail $detail)
    {
        $id = $detail->getId();
        $articleRepository = $this->getArticleRepository();
        $articleRepository->getRemoveImageQuery($id)->execute();

        $sql = 'DELETE FROM s_article_configurator_option_relations WHERE article_id = ?';
        /** @noinspection PhpUnhandledExceptionInspection */
        Shopware()->Db()->query($sql, [$id]);

        $articleRepository->getRemoveVariantTranslationsQuery($id)->execute();
        $articleRepository->getRemoveDetailQuery($id)->execute();
    }

    protected function getArticleRepository() {
        return $this->articleRepository ?? $this->articleRepository = $this->modelManager->getRepository(Article::class);
    }
}
