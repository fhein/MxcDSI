<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ImageMapper;
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

    /** @var array */
    protected $createdArticles;

    /** @var AssociatedArticlesMapper */
    protected $associatedArticlesMapper;

    /** @var CategoryMapper */
    protected $categoryMapper;

    /** @var ImageMapper */
    protected $imageMapper;

    /** @var ArticleTool */
    protected $articleTool;

    /**
     * ProductMapper constructor.
     *
     * @param ArticleTool $articleTool
     * @param DetailMapper $detailMapper
     * @param ImageMapper $imageMapper
     * @param CategoryMapper $categoryMapper
     * @param AssociatedArticlesMapper $associatedArticlesMapper
     */
    public function __construct(
        ArticleTool $articleTool,
        DetailMapper $detailMapper,
        ImageMapper $imageMapper,
        CategoryMapper $categoryMapper,
        AssociatedArticlesMapper $associatedArticlesMapper
    ) {
        $this->articleTool = $articleTool;
        $this->detailMapper = $detailMapper;
        $this->imageMapper = $imageMapper;
        $this->categoryMapper = $categoryMapper;
        $this->associatedArticlesMapper = $associatedArticlesMapper;
    }

    public function init()
    {
        $this->createdArticles = [];
    }

    public function createArticle(Product $product)
    {
        $valid = $product->isValid();
        if (! $valid) return false;
        $article = $product->getArticle();
        if ($article) return true;

        $article = new Article();
        $this->modelManager->persist($article);
        $product->setArticle($article);
        $this->createdArticles[] = $product->getId();

        $this->configureArticle($product, true);
        $this->activateArticle($product);
        $this->modelManager->flush(); //temporary
        return true;
    }

    /**
     * Create/Update the Shopware article associated to the active InnoCigs article.
     * Return true, if the product
     *
     * @param Product $product
     * @param bool $create
     * @param bool|null $forceUpdate
     * @return bool
     */
    public function updateArticle(Product $product, bool $create, bool $forceUpdate = null):  bool
    {
        $article = $product->getArticle();
        if (! $article) {
            if ($create) return $this->createArticle($product);
            return false;
        }
        if (! $product->isValid()) {
            $this->deleteArticles([$product]);
            return false;
        }
        $forceUpdate = $forceUpdate ?? false;

        $this->configureArticle($product, $forceUpdate);
        $this->activateArticle($product);
        $this->modelManager->flush(); // temporary
        return true;
    }

    public function updateArticleStructure(Product $product)
    {
        $article = $product->getArticle();
        if (! $article) return;
        if (! $this->detailMapper->needsStructureUpdate($product)) return;
        $this->detailMapper->map($product);
    }

    public function updateArticles(array $products, bool $create = false)
    {
        $this->createdArticles = [];
        foreach ($products as $product) {
            $this->updateArticle($product, $create);
        }
        $this->modelManager->flush();
    }

    /**
     * @param Product $product
     * @param bool $create
     * @return bool
     *
     */
    public function controllerUpdateArticle(Product $product, bool $create = false)
    {
        $this->controllerUpdateArticles([$product], $create);
        return $product->isActive();
    }

    public function controllerUpdateArticles(array $products, bool $create = false)
    {
        $this->updateArticles($products, $create);
        // @todo: Temporary
        $this->activateArticles($products, true);
        $this->associatedArticlesMapper->updateArticleLinks($this->createdArticles);
        $this->associatedArticlesMapper->setAssociatedArticles($this->createdArticles);
        $this->modelManager->flush();
    }

    public function createRelatedArticles(array $products, bool $recursive = false)
    {
        $associatedProducts = $this->associatedArticlesMapper->getRelatedProducts($products, $recursive);

        $this->createdArticles = [];
        foreach ($associatedProducts as $product) {
            $this->createArticle($product);
        }
        $this->associatedArticlesMapper->updateArticleLinks($this->createdArticles);
        $this->associatedArticlesMapper->setAssociatedArticles($this->createdArticles);
        $this->modelManager->flush();
    }

    public function createSimilarArticles(array $products, bool $recursive = false)
    {
        $associatedProducts = $this->associatedArticlesMapper->getSimilarProducts($products, $recursive);
        $this->createdArticles = [];
        foreach ($associatedProducts as $product) {
            $this->createArticle($product);
        }
        $this->associatedArticlesMapper->updateArticleLinks($this->createdArticles);
        $this->associatedArticlesMapper->setAssociatedArticles($this->createdArticles);
        $this->modelManager->flush();
    }

    public function deleteArticles(array $products)
    {
        foreach ($products as $product) {
            $this->detailMapper->deleteArticle($product);
        }
    }

    /**
     * Update all related and similar articles
     * @param array $associatedProductIds
     */
    public function updateAssociatedProducts(array $associatedProductIds)
    {
        $this->associatedArticlesMapper->updateArticleLinks($associatedProductIds);
    }

    ///////////////////////////////////////////////////////////////
    /// Article activation/deactivation
    ///
    public function controllerActivateArticles(array $products, bool $active = null, bool $create = false)
    {
        $this->controllerUpdateArticles($products, $create);
        $this->activateArticles($products, $active);
    }

    protected function activateArticles(array $products, bool $active = null)
    {
        foreach ($products as $product) {
            $this->activateArticle($product, $active);
        }
        $this->modelManager->flush();
    }

    protected function activateArticle(Product $product, bool $active = null)
    {
        // if no explicit value is supplied we take the product's active state
        $active = $active ?? $product->isActive();

        /** @var Article $article */
        $article = $product->getArticle();
        $active = $product->isValid() && $active && $article !== null;

        $product->setActive($active);

        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $this->detailMapper->setDetailActive($variant, $active);
        }

        if ($article) $article->setActive($active);
    }


    ///////////////////////////////////////////////////////////////
    /// Article acceptance
    ///
    public function acceptArticle(Product $product, bool $accepted)
    {
        $product->setAccepted($accepted);
        if (! $accepted) {
            $this->detailMapper->deleteArticle($product);
        }
    }

    /**
     * @param Product $product
     * @param bool $forceUpdate
     */
    protected function configureArticle(Product $product, bool $forceUpdate): void
    {
        $this->setArticleProperties($product, $forceUpdate);
        $this->detailMapper->map($product);
        PriceMapper::setReferencePrice($product);
        $this->imageMapper->setArticleImages($product);
    }

    /**
     * Set the properties of the Shopware article associated to the given InnoCigs article.
     *
     * @param Product $product
     * @param bool $created
     */
    protected function setArticleProperties(Product $product, bool $created)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (! $article) return;

        $probe = $article->getDescription();
        if ($created || !$probe || $probe === '') {
            $article->setDescription('');
        }

        $probe = $article->getKeywords();
        if ($created || !$probe || $probe === '') {
            $article->setKeywords('');
        }

        $probe = $article->getMetaTitle();
        if ($created || !$probe || $probe === '') {
            $metaTitle = preg_replace('~\(\d+ Stück pro Packung\)~', '', $product->getName());
            $article->setMetaTitle($metaTitle);
        }

        $article->setName($product->getName());
        $article->setDescriptionLong($product->getDescription());

        $article->setTax(TaxTool::getTax($product->getTax()));
        $article->setSupplier(SupplierTool::getSupplier($product->getSupplier()));
        $this->categoryMapper->map($product);
    }
}
