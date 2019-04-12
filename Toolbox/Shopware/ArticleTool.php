<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Doctrine\DBAL\Statement;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareOptionMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\ArticleRepository;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Components\Api\Resource\Article as ArticleResource;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Configurator\Set;
use Shopware\Models\Article\Repository;

class ArticleTool {

    /** @var LoggerInterface */
    protected $log;

    /** @var Repository */
    protected $shopwareRepository;

    /** @var ArticleRepository */
    protected $repository;

    /** @var ModelManager  */
    protected $modelManager;

    /** @var ShopwareOptionMapper */
    protected $optionMapper;

    /** @var ArticleResource */
    protected $articleResource;

    /** @var Statement */
    protected $fixMainDetailsStatement;

    /** @var Statement */
    protected $setMainDetailsStatement;

    public function __construct(ModelManager $modelManager, ShopwareOptionMapper $optionMapper, LoggerInterface $log)
    {
        $this->modelManager = $modelManager;
        $this->log = $log;
        $this->articleResource = new ArticleResource();
        $this->articleResource->setManager($this->modelManager);
        $this->optionMapper = $optionMapper;
    }

    public function deleteArticle(Article $icArticle)
    {
        /** @var  ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        $configuratorSetName = 'mxc-set-' . $icArticle->getIcNumber();
        if ($set = $this->modelManager->getRepository(Set::class)->findOneBy(['name' => $configuratorSetName]))
        {
            $this->modelManager->remove($set);
        }
        $icArticle->setArticle(null);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->articleResource->delete($swArticle->getId());
    }

    public function setMainDetail(Variant $variant)
    {
        $swDetail = $variant->getDetail();
        if (! $swDetail) return;
        /** @var ShopwareArticle $swArticle */
        $swArticle = $variant->getArticle()->getArticle();
        if (! $swArticle) return;

        $oldMainDetail = $swArticle->getMainDetail();
        if ($oldMainDetail) {
            $oldMainDetail->setKind(2);
        }
        $swArticle->setMainDetail($swDetail);
        $swDetail->setKind(1);
    }

    public function deleteInvalidVariants(array $icArticles)
    {
        foreach ($icArticles as $icArticle) {
            $validVariants = $icArticle->getValidVariants();
            if ( empty($validVariants)) {
                $this->deleteArticle($icArticle);
            } else {
                $this->setMainDetail($validVariants[0]);
                $this->deleteInvalidDetails($icArticle);
            }
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    public function deleteInvalidDetails(Article $article)
    {
        $invalidVariants = $this->getRepository()->getInvalidVariants($article) ?? [];
        $repository = $this->getShopwareRepository();
        /** @var Variant $invalidVariant */
        $changed = count($invalidVariants) !== 0;
        foreach ($invalidVariants as $invalidVariant) {
            $swDetail = $invalidVariant->getDetail();
            if (! $swDetail || $swDetail->getKind === 1) continue;

            $id = $swDetail->getId();
            $repository->getRemoveImageQuery($id)->execute();

            $sql = 'DELETE FROM s_article_configurator_option_relations WHERE article_id = ?';
            /** @noinspection PhpUnhandledExceptionInspection */
            Shopware()->Db()->query($sql, [$id]);

            $repository->getRemoveVariantTranslationsQuery($id)->execute();
            $repository->getRemoveDetailQuery($id)->execute();
            $invalidVariant->setDetail(null);
        }
        /** @var ShopwareArticle $swArticle */
        $swArticle = $article->getArticle();
        if (! $swArticle || ! $changed) return;
        $swArticle->setConfiguratorSet($this->optionMapper->createConfiguratorSet($article));
    }

    /**
     * Deletes the detail record associated to the given variant object.
     *
     * @param Variant $icVariant
     */
    public function deleteDetail(Variant $icVariant)
    {
        $swDetail = $icVariant->getDetail();
        if (! $swDetail) return;

        $this->modelManager->remove($swDetail);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    public function cleanup()
    {
        // Find all articles pointing to a non existing main detail
        // and fix the main detail id to the first detail belonging
        // to the respective article.

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getFixMainDetailsStatement()->execute();

        // Set the kind of all main details to 1

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getSetMainDetailKindStatement()->execute();
    }

    protected function getFixMainDetailsStatement()
    {
        if (! $this->fixMainDetailsStatement) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->fixMainDetailsStatement = $this->modelManager->getConnection()->prepare(
                    'UPDATE s_articles a LEFT JOIN s_articles_details d ON d.id = a.main_detail_id '
                . 'SET a.main_detail_id = ( SELECT id FROM s_articles_details WHERE articleID = a.id LIMIT 1 ) '
                . 'WHERE d.id IS NULL; '
            );
        }
        return $this->fixMainDetailsStatement;
    }

    protected function getSetMainDetailKindStatement()
    {
        if (! $this->setMainDetailsStatement) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->setMainDetailsStatement = $this->modelManager->getConnection()->prepare(
                'UPDATE s_articles a, s_articles_details d SET d.kind = 1 WHERE d.id = a.main_detail_id;'
            );
        }
        return $this->setMainDetailsStatement;
    }

    protected function getShopwareRepository() {
        return $this->shopwareRepository ?? $this->shopwareRepository = $this->modelManager->getRepository(ShopwareArticle::class);
    }

    protected function getRepository() {
        return $this->repository ?? $this->repository = $this->modelManager->getRepository(Article::class);
    }
}
