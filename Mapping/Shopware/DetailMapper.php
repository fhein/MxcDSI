<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Shopware\Components\Api\Resource\Article as ArticleResource;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Set;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\Article as Attribute;

class DetailMapper implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    /** @var PriceMapper $priceMapper */
    protected $priceMapper;

    /** @var ArticleTool */
    protected $articleTool;

    /** @var OptionMapper */
    protected $optionMapper;

    /** @var DropshippersCompanion */
    private $companion;

    /** @var ArticleResource */
    private $articleResource;

    /** \Doctrine\ORM\EntityRepository */
    private $setRepository;

    /** @var ProductRepository */
    protected $productRepository;

    public function __construct(
        ArticleTool $articleTool,
        ArticleResource $articleResource,
        DropshippersCompanion $companion,
        PriceMapper $priceMapper,
        OptionMapper $optionMapper
    ) {
        $this->optionMapper = $optionMapper;
        $this->companion = $companion;
        $this->priceMapper = $priceMapper;
        $this->articleTool = $articleTool;
        $this->articleResource = $articleResource;
    }

    /**
     * Create/Update all Shopware details associated to the InnoCogs article's
     * variants.
     *
     * @param Product $product
     */
    public function map(Product $product): void
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (! $article) return;

        if (! $product->isValid()) {
            $this->deleteArticle($product);
            return;
        }

        $configuratorSet = $this->optionMapper->updateConfiguratorSet($product);
        $article->setConfiguratorSet($configuratorSet);

        $variants = $product->getVariants();

        $isMainDetail = true;
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $detail = $this->setDetail($variant);
            if ($detail === null) continue;

            $detail->setKind(2);
            if ($isMainDetail) {
                $detail->setKind(1);
                $article->setMainDetail($detail);
                $article->setAttribute($detail->getAttribute());
                $isMainDetail = false;
            }
        }
    }

    /**
     * Set the properties of the Shopware detail associated to the given InnoCigs variant.
     * If the detail does not exist, it will be created.
     *
     * @param Variant $variant
     * @return Detail|null
     */
    public function setDetail(Variant $variant)
    {
        $detail = $variant->getDetail();

        if ($detail && ! $variant->isValid()) {
            $this->articleTool->deleteDetail($detail);
            $variant->setDetail(null);
            return null;
        }

        if ($detail) {
            // Update existing detail
            $this->setShopwareDetailProperties($variant);
            $configuratorOptions = $detail->getConfiguratorOptions();
            $configuratorOptions->clear();
            $detail->setConfiguratorOptions(new ArrayCollection($variant->getShopwareOptions()));
            return $detail;
        }

        if (! $variant->isValid()) {
            return null;
        }

        $product = $variant->getProduct();
        $article = $product->getArticle();

        if (!$article) return null;

        $detail = new Detail();
        $this->modelManager->persist($detail);
        // The next two settings have to be made upfront because the later code relies on these
        $variant->setDetail($detail);
        $detail->setArticle($article);

        // The class \Shopware\Models\Attribute\Product ist part of the Shopware attribute system.
        // It gets (re)generated automatically by Shopware core, when attributes are added/removed
        // via the attribute crud service. It is located in \var\cache\production\doctrine\attributes.
        $attribute = new Attribute();
        $detail->setAttribute($attribute);

        $this->setShopwareDetailProperties($variant);

        // All valid details are marked active
        $detail->setActive(true);

        // set next three properties only on detail creation
        $this->priceMapper->setRetailPrices($variant);
        $detail->setShippingTime(5);
        $detail->setLastStock(0);

        // Note: shopware options were added non persistently to variants when configurator set was created
        $detail->setConfiguratorOptions(new ArrayCollection($variant->getShopwareOptions()));

        return $detail;
    }

    /**
     * Set the properties of the Shopware detail associated to the given InnoCigs variant.
     *
     * @param Variant $variant
     */
    public function setShopwareDetailProperties(Variant $variant)
    {
        $detail = $variant->getDetail();
        if (!$detail) {
            return;
        }

        $detail->setNumber($variant->getNumber());
        $detail->setEan($variant->getEan());
        $purchasePrice = floatval(str_replace(',', '.', $variant->getPurchasePrice()));
        $detail->setPurchasePrice($purchasePrice);

        // @todo: Transfer the custom attributes to shopware or not?
//        $attribute = $detail->getAttribute();
//        $product = $variant->mapProduct();

//        /** @noinspection PhpUndefinedMethodInspection */
//        $attribute->setMxcDsiBrand($product->getBrand());
//        /** @noinspection PhpUndefinedMethodInspection */
//        $attribute->setMxcDsiSupplier($product->getSupplier());
//        /** @noinspection PhpUndefinedMethodInspection */
//        $attribute->setMxcDsiFlavor($product->getFlavor());
//        /** @noinspection PhpUndefinedMethodInspection */
//        $attribute->setMxcDsiMaster($product->getIcNumber());
//        /** @noinspection PhpUndefinedMethodInspection */
//        $attribute->setMxcDsiType($product->getType());
    }

    /**
     * Set the Shopware detail attributes for the dropship plugin.
     *
     * @param Variant $variant
     * @param bool $active
     */
    public function setDetailActive(Variant $variant, bool $active)
    {
        $detail = $variant->getDetail();

        $active = $active && $variant->isValid() && $detail !== null;
        $variant->setActive($active);

        if (!$detail) return;

        $detail->setActive($variant->isValid());
        $this->companion->configureDropship($variant);
    }

    public function deleteArticle(Product $product)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (! $article) return;

        $configuratorSetName = 'mxc-set-' . $product->getIcNumber();
        if ($set = $this->getSetRepository()->findOneBy(['name' => $configuratorSetName]))
        {
            $this->modelManager->remove($set);
        }

        $product->setArticle(null);
        $product->setActive(false);
        $product->setLinked(false);

        $this->articleResource->delete($article->getId());
    }

    protected function getProductRepository()
    {
        return $this->productRepository ?? $this->productRepository = $this->modelManager->getRepository(Product::class);
    }

    protected function getSetRepository()
    {
        return $this->setRepository ?? $this->setRepository = $this->modelManager->getRepository(Set::class);
    }
}