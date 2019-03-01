<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Repository;

class ArticleTool {

    protected $repository;

    public function deleteArticle(Article $article)
    {
        $articleId = $article->getId();
        $this->removePrices($articleId);
        $this->removeArticleEsd($articleId);
        $this->removeAttributes($articleId);
        $this->removeArticleDetails($articleId);
        $this->removeArticleTranslations($article);
        Shopware()->Models()->remove($article);
        /** @noinspection PhpUnhandledExceptionInspection */
        Shopware()->Models()->flush();
    }

    public function deleteArticleDetail(Detail $detail)
    {
        $article = $detail->getArticle();
        if ($detail->getId() !== $article->getMainDetail()->getId()) {
            $modelManager = Shopware()->Models();
            $modelManager->remove($detail);
            /** @noinspection PhpUnhandledExceptionInspection */
            $modelManager->flush();
        }
    }


    protected function removePrices($articleId)
    {
        $query = $this->getRepository()->getRemovePricesQuery($articleId);
        $query->execute();
    }

    protected function removeArticleEsd($articleId)
    {
        $query = $this->getRepository()->getRemoveESDQuery($articleId);
        $query->execute();
    }

    protected function removeAttributes($articleId)
    {
        $query = $this->getRepository()->getRemoveAttributesQuery($articleId);
        $query->execute();
    }

    protected function removeArticleDetails($articleId)
    {
        $sql = 'SELECT id FROM s_articles_details WHERE articleID = ? AND kind != 1';
        $details = Shopware()->Db()->fetchAll($sql, [$articleId]);

        foreach ($details as $detail) {
            $query = $this->getRepository()->getRemoveImageQuery($detail['id']);
            $query->execute();

            $sql = 'DELETE FROM s_article_configurator_option_relations WHERE article_id = ?';
            /** @noinspection PhpUnhandledExceptionInspection */
            Shopware()->Db()->query($sql, [$detail['id']]);

            $query = $this->getRepository()->getRemoveVariantTranslationsQuery($detail['id']);
            $query->execute();

            $query = $this->getRepository()->getRemoveDetailQuery($detail['id']);
            $query->execute();
        }
    }

    protected function removeArticleTranslations($articleId)
    {
        $query = $this->getRepository()->getRemoveArticleTranslationsQuery($articleId);
        $query->execute();

        $sql = 'DELETE FROM s_articles_translations WHERE articleID = ?';
        Shopware()->Container()->get('dbal_connection')->executeQuery($sql, [$articleId]);
    }

    protected function getRepository() : Repository
    {
        if ($this->repository === null) {
            $this->repository = Shopware()->Models()->getRepository(Article::class);
        }

        return $this->repository;
    }
}
