<?php /** @noinspection PhpDocMissingThrowsInspection */

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

    /** @var array $createdArticles */
    protected $createdArticles;

    /** @var array */
    protected $updatedProducts;

    /** @var AssociatedArticlesMapper $associatedArticlesMapper */
    protected $associatedArticlesMapper;

    /** @var CategoryMapper $categoryMapper */
    protected $categoryMapper;

    /** @var ImageMapper $imageMapper */
    protected $imageMapper;

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
        $this->updatedProducts = [];
    }

    /**
     * Create/Update the Shopware article associated to the active InnoCigs article.
     * Return true, if the product
     *
     * @param Product $product
     * @param bool $create
     * @return bool
     */
    public function updateArticle(Product $product, bool $create):  bool
    {
        $valid = $product->isValid();
        $article = $product->getArticle();

        // delete article if the product is not valid
        if (! $valid && $article) {
            $this->deleteArticles([$article]);
        }
        if (! $valid) return false;

        // create article if it does not exist already and creation is requested
        $created  = false;
        if (! $article && $create) {
            $article = new Article();
            $this->modelManager->persist($article);
            $product->setArticle($article);
            $this->createdArticles[] = $product->getIcNumber();
            $created = true;
        }
        if (! $article) return false;

        $this->configureArticle($product, $created);
        $this->activateArticle($product);
        $this->updatedProducts[$product->getIcNumber()] = $product;

        return true;
    }

    public function updateArticles(array $products, bool $create = false)
    {
        foreach ($products as $product) {
            $this->updateArticle($product, $create);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
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
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->controllerUpdateArticles([$product], $create);
        return $product->isActive();
    }

    public function controllerUpdateArticles(array $products, bool $create = false)
    {
        $this->createdArticles = [];
        $this->updatedProducts = [];

        $this->updateArticles($products, $create);
        $this->activateArticles($products);
        $this->associatedArticlesMapper->processAssociatedProducts($this, $this->updatedProducts, $create);

        if (! empty($this->createdArticles)) {
            $this->associatedArticlesMapper->updateArticleLinks($this->createdArticles);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();

    }

    public function deleteArticles(array $products)
    {
        foreach ($products as $product) {
            $this->detailMapper->deleteArticle($product);
        }
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
        if (! $accepted) $this->detailMapper->deleteArticle($product);
    }

    /**
     * @param Product $product
     * @param bool $created
     */
    protected function configureArticle(Product $product, bool $created): void
    {
        $this->setArticleProperties($product, $created);
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

        // update description if not already set or if force is set
        $probe = $article->getDescriptionLong();
        if ($created || !$probe || $probe === '') {
            $article->setDescriptionLong($product->getDescription());
        }

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
            $metaTitle = 'Vapee.de: ' . preg_replace('~\(\d+ Stück pro Packung\)~', '', $product->getName());
            $article->setMetaTitle($metaTitle);
        }

        $probe = $article->getName();
        if ($created || !$probe || $probe === '') {
            $article->setName($product->getName());
        }

        $article->setTax(TaxTool::getTax($product->getTax()));
        $article->setSupplier(SupplierTool::getSupplier($product->getSupplier()));
        $this->categoryMapper->map($product);
    }
}
