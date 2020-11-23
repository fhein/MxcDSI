<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipIntegrator\Mapping\Shopware\CategoryMapper;
use MxcDropshipIntegrator\Mapping\Shopware\DetailMapper;
use MxcDropshipIntegrator\Mapping\Shopware\ImageMapper;
use MxcDropshipIntegrator\Mapping\Shopware\PriceMapper;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcCommons\Toolbox\Shopware\SupplierTool;
use MxcCommons\Toolbox\Shopware\TaxTool;
use Shopware\Models\Article\Article;

class ProductMapper implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;
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
        // article attributes do not exist before flush
        $this->setArticleAttributes($product, $article);
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
        // article attributes do not exist before flush
        $this->setArticleAttributes($product, $article);
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

        //--- setLastStock will get deprecated with Shopware 5.7
        if (method_exists($article, 'setLastStock')) {
            $article->setLastStock(1);
        }

        $article->setDescription($product->getSeoDescription());
        $article->setMetaTitle($product->getSeoTitle());
        $article->setKeywords($product->getSeoKeywords());

        $article->setName($product->getName());
        $article->setDescriptionLong($product->getDescription());

        // findet oder erzeugt ein Tax Object mit dem aktuellen Mehrwertsteuersatz
        $article->setTax(TaxTool::getTax());

        $supplierName = $product->getSupplier();
        if ($supplierName === 'InnoCigs') $supplierName = $product->getBrand();
        $supplier = SupplierTool::getSupplier($supplierName);
        $article->setSupplier($supplier);

        // set supplier page meta information
        $title = 'E-Zigaretten: Unsere Produkte von %s';
        $description = 'Produkte für Dampfer von %s ✓ vapee.de bietet ein breites Sortiment von E-Zigaretten und E-Liquids zu fairen Preisen ► Besuchen Sie uns!';
        $metaTitle = sprintf($title, $supplierName);
        $metaDescription = sprintf($description, $supplierName);
        SupplierTool::setSupplierMetaInfo($supplier, $metaTitle, $metaDescription, $supplierName);

        $this->categoryMapper->map($product);
    }

    protected function setArticleAttributes(Product $product, Article $article)
    {
        $seoUrl = $product->getSeoUrl();
        if (! empty($seoUrl)) {
            ArticleTool::setArticleAttribute($article, 'attr4', $seoUrl);
        }
        $flavor = $product->getFlavor();
        if (! empty($flavor))
        {
            // displayed in article list
            ArticleTool::setArticleAttribute($article, 'mxcbc_flavor', $flavor);

            // convert flavor list to format used by Ajax Power Filter
            $flavorFilter = array_map('trim', explode(',', $flavor));
            $flavorFilter = '|' . implode('|', $flavorFilter) . '|';
            ArticleTool::setArticleAttribute($article, 'mxcbc_flavor_filter', $flavorFilter);
        }
        $type = $product->getType();
        $filterType = $this->classConfig['filter_product_type'][$type] ?? 'unbekannt';
        ArticleTool::setArticleAttribute($article, 'mxcbc_product_type', $filterType);
    }
}
