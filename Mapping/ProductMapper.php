<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Mapping\Shopware\ArticleCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\OptionMapper;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use MxcDropshipInnocigs\Toolbox\Shopware\SupplierTool;
use MxcDropshipInnocigs\Toolbox\Shopware\TaxTool;
use Shopware\Models\Article\Article;

class ProductMapper implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var DetailMapper */
    protected $detailMapper;

    /** @var array $createdArticles */
    protected $createdArticles;

    /** @var AssociatedArticlesMapper $associatedArticlesMapper */
    protected $associatedArticlesMapper;

    /** @var ArticleCategoryMapper $categoryMapper */
    protected $categoryMapper;

    /** @var OptionMapper $optionMapper */
    protected $optionMapper;

    /** @var ImageMapper $imageMapper */
    protected $imageMapper;

    protected $articleTool;

    /**
     * ProductMapper constructor.
     *
     * @param ArticleTool $articleTool
     * @param OptionMapper $optionMapper
     * @param DetailMapper $detailMapper
     * @param ImageMapper $imageMapper
     * @param ArticleCategoryMapper $categoryMapper
     * @param AssociatedArticlesMapper $associatedArticlesMapper
     */
    public function __construct(
        ArticleTool $articleTool,
        OptionMapper $optionMapper,
        DetailMapper $detailMapper,
        ImageMapper $imageMapper,
        ArticleCategoryMapper $categoryMapper,
        AssociatedArticlesMapper $associatedArticlesMapper
    ) {
        $this->articleTool = $articleTool;
        $this->optionMapper = $optionMapper;
        $this->detailMapper = $detailMapper;
        $this->imageMapper = $imageMapper;
        $this->categoryMapper = $categoryMapper;
        $this->associatedArticlesMapper = $associatedArticlesMapper;
    }

    public function setArticleAcceptedState(array $products, bool $accepted)
    {
        /** @var Product $product */
        foreach ($products as $product) {
            $product->setAccepted($accepted);
            if ($accepted) continue;
            $this->setArticleActive($product);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
        if (! $accepted) {
            $this->detailMapper->deleteInvalidDetails($products);
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
     * @param array $products
     * @param bool $createArticlesNotActive
     */
    public function processStateChangesProductList(array $products, bool $createArticlesNotActive = false)
    {
        $this->createdArticles = [];
        $activeArticles = [];
        /** @var Product $product */
        foreach ($products as $product) {
            if (! $this->setArticle($product, $createArticlesNotActive)) {
                $this->setArticleActive($product);
                continue;
            }
            $icNumber = $product->getIcNumber();
            $activeArticles[$icNumber] = $activeArticles[$icNumber] ?? $product;
        }

        $this->processAssociatedArticles($createArticlesNotActive, $activeArticles);

        // Update all articles with similar or related articles referencing articles
        // that we just created.
        if (! empty($this->createdArticles)) {
            $this->associatedArticlesMapper->updateArticleLinks($this->createdArticles);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    public function updateArticles(array $products)
    {
        $this->processStateChangesProductList($products, false);
    }

    /**
     * @param Product $product
     * @param bool $createArticlesNotActive
     * @return bool
     *
     *@see processStateChangesProductList
     *
     */
    public function processStateChangesArticle(Product $product, bool $createArticlesNotActive = false)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->processStateChangesProductList([$product], $createArticlesNotActive);
        return $product->isActive();
    }

    /**
     * @param bool $createArticlesNotActive
     * @param array $activeArticles
     */
    public function processAssociatedArticles(bool $createArticlesNotActive, array $activeArticles)
    {
        $associatedArticles = $this->associatedArticlesMapper->getAssociatedProducts($activeArticles);

        /** @var Product $product */
        foreach ($associatedArticles as $product) {
            if ($this->setArticle($product, $createArticlesNotActive)) {
                $activeArticles[$product->getIcNumber()] = $product;
            }
            $this->setArticleActive($product);
        }

        foreach ($activeArticles as $product) {
            $this->associatedArticlesMapper->setRelatedArticles($product);
            $this->associatedArticlesMapper->setSimilarArticles($product);
            $this->setArticleActive($product);
        }
    }

    protected function createArticle(Product $product)
    {
        $article = new Article();
        $this->modelManager->persist($article);
        $product->setArticle($article);
        $product->setLinked(true);
        $this->createdArticles[] = $product->getIcNumber();
        return $article;
    }

    /**
     * Create/Update the Shopware article associated to the active InnoCigs article.
     *
     * @param Product $product
     * @param bool $allowCreate
     * @return bool
     */
    protected function setArticle(Product $product, bool $allowCreate): bool
    {
        if (! $product->isValid()) {
            $product->setActive(false);
            return false;
        }

        $article = $product->getArticle();
        $created = false;

        if ($article === null) {
            if (! $allowCreate) return false;
            $article = $this->createArticle($product);
            $created = true;
        }

        $article->setConfiguratorSet($this->optionMapper->createConfiguratorSet($product));

        $this->setArticleProperties($product, $created);

        $this->detailMapper->map($product);

        PriceMapper::setReferencePrice($product);

        $this->imageMapper->setArticleImages($product);
        // We have to flush each article in order
        // to get the newly created categories
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();

        return true;
    }

    /**
     * Set the properties of the Shopware article associated to the given InnoCigs article.
     *
     * @param Product $product
     * @param bool $force true: overwrite
     */
    protected function setArticleProperties(Product $product, bool $force)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (! $article) return;

        // update description if not already set or if force is set
        $probe = $article->getDescriptionLong();
        if ($force || !$probe || $probe === '') {
            $article->setDescriptionLong($product->getDescription());
        }

        $probe = $article->getDescription();
        if ($force || !$probe || $probe === '') {
            $article->setDescription('');
        }

        $probe = $article->getKeywords();
        if ($force || !$probe || $probe === '') {
            $article->setKeywords('');
        }

        $probe = $article->getMetaTitle();
        if ($force || !$probe || $probe === '') {
            $metaTitle = 'Vapee.de: ' . preg_replace('~\(\d+ Stück pro Packung\)~', '', $product->getName());
            $article->setMetaTitle($metaTitle);
        }

        $probe = $article->getName();
        if ($force || !$probe || $probe === '') {
            $article->setName($product->getName());
        }

        $article->setTax(TaxTool::getTax($product->getTax()));
        $article->setSupplier(SupplierTool::getSupplier($product->getSupplier()));
        $this->categoryMapper->map($product);
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
     * @param Product $product
     */
    public function setArticleActive(Product $product)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        $active = $product->isValid() && $product->isActive() && $article !== null;

        $product->setActive($active);

        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $this->detailMapper->setDetailActive($variant, $active);
        }

        if ($article) {
            $article->setActive($active);
        }
    }

}