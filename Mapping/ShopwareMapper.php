<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareAssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareDetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwareOptionMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ShopwarePriceMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use MxcDropshipInnocigs\Toolbox\Shopware\SupplierTool;
use MxcDropshipInnocigs\Toolbox\Shopware\TaxTool;
use Shopware\Components\Api\Resource\Article as ArticleResource;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Detail;

class ShopwareMapper
{
    /** @var LoggerInterface $log */
    protected $log;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ShopwareDetailMapper */
    protected $detailMapper;

    /** @var array $createdArticles */
    protected $createdArticles;

    /** @var ShopwareAssociatedArticlesMapper $associatedArticlesMapper */
    protected $associatedArticlesMapper;

    /** @var ShopwareCategoryMapper $categoryMapper */
    protected $categoryMapper;

    /** @var ShopwareOptionMapper $optionMapper */
    protected $optionMapper;

    /** @var ShopwareImageMapper $imageMapper */
    protected $imageMapper;

    protected $articleTool;

    /**
     * ShopwareMapper constructor.
     *
     * @param ModelManager $modelManager
     * @param ArticleTool $articleTool
     * @param ShopwareOptionMapper $optionMapper
     * @param ShopwareDetailMapper $detailMapper
     * @param ShopwareImageMapper $imageMapper
     * @param ShopwareCategoryMapper $categoryMapper
     * @param ShopwareAssociatedArticlesMapper $associatedArticlesMapper
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ArticleTool $articleTool,
        ShopwareOptionMapper $optionMapper,
        ShopwareDetailMapper $detailMapper,
        ShopwareImageMapper $imageMapper,
        ShopwareCategoryMapper $categoryMapper,
        ShopwareAssociatedArticlesMapper $associatedArticlesMapper,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->articleTool = $articleTool;
        $this->optionMapper = $optionMapper;
        $this->detailMapper = $detailMapper;
        $this->imageMapper = $imageMapper;
        $this->categoryMapper = $categoryMapper;
        $this->associatedArticlesMapper = $associatedArticlesMapper;
        $this->log = $log;
    }

    public function setArticleAcceptedState(array $icArticles, bool $accepted)
    {
        /** @var Article $icArticle */
        foreach ($icArticles as $icArticle) {
            $icArticle->setAccepted($accepted);
            if ($accepted) continue;
            $this->setShopwareArticleActive($icArticle);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
        if (! $accepted) {
            $this->articleTool->deleteInvalidVariants($icArticles);
        }
    }

    /**
     * Main entry point if the $active, $accepted or $linked state of a list of
     * InnoCigs articles changes.
     *
     * $accepted === false  the associated Shopware article gets deactivated
     *                      (articles which are not accepted do not get created
     *                      regardless of the other settings)
     *
     * $active === true     the Shopware article gets created/updated and activated
     * $active === false    the associated Shopware article gets deactivated without
     *                      getting updated
     *
     * $linked === true     the Shopware article gets created
     *
     * $createArticlesNotActive === true    create article even if it is not $active
     * $createArticlesNotActive === false   don't create articles which are not $active
     *
     * @param array $icArticles
     * @param bool $createArticlesNotActive
     */
    public function processStateChangesArticleList(array $icArticles, bool $createArticlesNotActive = false)
    {
        $this->createdArticles = [];
        $activeArticles = [];
        /** @var Article $icArticle */
        foreach ($icArticles as $icArticle) {
            if (! $this->setShopwareArticle($icArticle, $createArticlesNotActive)) {
                $this->setShopwareArticleActive($icArticle);
                continue;
            }
            $icNumber = $icArticle->getIcNumber();
            $activeArticles[$icNumber] = $activeArticles[$icNumber] ?? $icArticle;
        }

        $this->processAssociatedArticles($createArticlesNotActive, $activeArticles);

        // Update all articles with similar or related articles referencing articles
        // that we just created.
        if (! empty($this->createdArticles)) {
            $this->updateArticleLinks($this->createdArticles);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    public function updateShopwareArticles(array $icArticles)
    {
        $this->processStateChangesArticleList($icArticles, false);
    }

    /**
     * @see processStateChangesArticleList
     *
     * @param Article $icArticle
     * @param bool $createArticlesNotActive
     * @return bool
     *
     */
    public function processStateChangesArticle(Article $icArticle, bool $createArticlesNotActive = false)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->processStateChangesArticleList([$icArticle], $createArticlesNotActive);
        return $icArticle->isActive();
    }

    /**
     * @param bool $createArticlesNotActive
     * @param array $activeArticles
     */
    public function processAssociatedArticles(bool $createArticlesNotActive, array $activeArticles)
    {
        $associatedArticles = $this->associatedArticlesMapper->getAssociatedArticles($activeArticles);

        /** @var Article $icArticle */
        foreach ($associatedArticles as $icArticle) {
            if ($this->setShopwareArticle($icArticle, $createArticlesNotActive)) {
                $activeArticles[$icArticle->getIcNumber()] = $icArticle;
            }
            $this->setShopwareArticleActive($icArticle);
        }

        foreach ($activeArticles as $icArticle) {
            $this->associatedArticlesMapper->setRelatedArticles($icArticle);
            $this->associatedArticlesMapper->setSimilarArticles($icArticle);
            $this->setShopwareArticleActive($icArticle);
        }
    }

    protected function createShopwareArticle(Article $icArticle)
    {
        // Create Shopware Article
        $swArticle = new ShopwareArticle();
        $this->modelManager->persist($swArticle);
        $icArticle->setArticle($swArticle);
        $icArticle->setLinked(true);
        $this->createdArticles[] = $icArticle->getIcNumber();
        return $swArticle;
    }

    /**
     * Create/Update the Shopware article associated to the active InnoCigs article.
     *
     * @param Article $icArticle
     * @param bool $allowCreate
     * @return bool
     */
    protected function setShopwareArticle(Article $icArticle, bool $allowCreate): bool
    {
        if (! $icArticle->isValid()) {
            $icArticle->setActive(false);
            return false;
        }

        $swArticle = $icArticle->getArticle();
        $created = false;

        if ($swArticle === null) {
            if (! $allowCreate) return false;
            $swArticle = $this->createShopwareArticle($icArticle);
            $created = true;
        }

        $swArticle->setConfiguratorSet($this->optionMapper->createConfiguratorSet($icArticle));

        $this->setShopwareArticleProperties($icArticle, $created);

        $this->detailMapper->map($icArticle);

        ShopwarePriceMapper::setReferencePrice($icArticle);

        $this->imageMapper->setArticleImages($icArticle);
        // We have to flush each article in order
        // to get the newly created categories
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();

        return true;
    }

    /**
     * Set the properties of the Shopware article associated to the given InnoCigs article.
     *
     * @param Article $icArticle
     * @param bool $force true: overwrite
     */
    protected function setShopwareArticleProperties(Article $icArticle, bool $force)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (! $swArticle) return;

        // update description if not already set or if force is set
        $probe = $swArticle->getDescriptionLong();
        if ($force || !$probe || $probe === '') {
            $swArticle->setDescriptionLong($icArticle->getDescription());
        }

        $probe = $swArticle->getDescription();
        if ($force || !$probe || $probe === '') {
            $swArticle->setDescription('');
        }

        $probe = $swArticle->getKeywords();
        if ($force || !$probe || $probe === '') {
            $swArticle->setKeywords('');
        }

        $probe = $swArticle->getMetaTitle();
        if ($force || !$probe || $probe === '') {
            $metaTitle = 'Vapee.de: ' . preg_replace('~\(\d+ Stück pro Packung\)~', '', $icArticle->getName());
            $swArticle->setMetaTitle($metaTitle);
        }

        $probe = $swArticle->getName();
        if ($force || !$probe || $probe === '') {
            $swArticle->setName($icArticle->getName());
        }

        $swArticle->setTax(TaxTool::getTax($icArticle->getTax()));
        $swArticle->setSupplier(SupplierTool::getSupplier($icArticle->getSupplier()));
        $this->categoryMapper->map($icArticle);
    }

    /**
     * Set the shopware article active state to according to the $active state
     * of the given InnoCigs article. Can modify the dropship active state of the
     * Shopware Details. If the InnoCigs article is active, dropship gets
     * enabled for all active Shopware details and disabled for non active
     * Shopware details. If the article is not active, dropship gets disabled
     * for all Shopware details.
     *
     * If the InnoCigs article is not valid any longer or there is no corresponding
     * Shopware article, the InnoCigs article gets deactivated.
     *
     * @param Article $icArticle
     */
    public function setShopwareArticleActive(Article $icArticle)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        $active = $icArticle->isValid() && $icArticle->isActive() && $swArticle !== null;

        $icArticle->setActive($active);

        $icVariants = $icArticle->getVariants();
        /** @var Variant $icVariant */
        foreach ($icVariants as $icVariant) {
            $this->detailMapper->setShopwareDetailActive($icVariant, $active);
        }

        if ($swArticle) {
            $swArticle->setActive($active);
        }
    }

    public function removeShopwareDetail(Detail $swDetail)
    {
        if ($swDetail->getKind() == 1) {
            $articleResource = new ArticleResource();
            $articleResource->setManager($this->modelManager);
            /** @noinspection PhpUnhandledExceptionInspection */
            $articleResource->delete($swDetail->getArticle()->getId());
        } else {
            Shopware()->Models()->remove($swDetail);
        }
    }

    /**
     * Update the related and similar article lists of all Shopware articles
     * where the corresponding icArticle has related and similar articles from
     * the given $icArticles array.
     *
     * @param array $icArticles
     */
    protected function updateArticleLinks(array $icArticles) {
        if (count($icArticles) === 0) return;

        $repository = $this->modelManager->getRepository(Article::class);

        $articlesWithRelatedNewArticles = $repository->getHavingRelatedArticles($icArticles);
        foreach ($articlesWithRelatedNewArticles as $icArticle) {
            $this->associatedArticlesMapper->setRelatedArticles($icArticle);
        }

        $articlesWithSimilarNewArticles = $repository->getHavingSimilarArticles($icArticles);
        foreach ($articlesWithSimilarNewArticles as $icArticle) {
            $this->associatedArticlesMapper->setSimilarArticles($icArticle);
        }
    }

}
