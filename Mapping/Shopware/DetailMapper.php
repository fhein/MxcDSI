<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping\Shopware;

use Doctrine\Common\Collections\ArrayCollection;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\ProductRepository;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use Shopware\Components\Api\Resource\Article as ArticleResource;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Set;
use Shopware\Models\Article\Detail;

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


    /** \Doctrine\ORM\EntityRepository */
    private $setRepository;

    /** @var ProductRepository */
    protected $productRepository;

    public function __construct(
        ArticleTool $articleTool,
        DropshippersCompanion $companion,
        PriceMapper $priceMapper,
        OptionMapper $optionMapper
    ) {
        $this->optionMapper = $optionMapper;
        $this->companion = $companion;
        $this->priceMapper = $priceMapper;
        $this->articleTool = $articleTool;
    }

    public function needsStructureUpdate(Product $product)
    {
        return $this->optionMapper->needsUpdate($product);
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

        [$needsOptionUpdate, $configuratorSet] = $this->optionMapper->updateConfiguratorSet($product);
        $article->setConfiguratorSet($configuratorSet);

        $variants = $product->getVariants();

        // get product flavor
        $flavor = $product->getFlavor();
        if (! empty($flavor)) {
            $flavor = implode(', ', array_map('trim', explode(',', $flavor)));
        }


        $isMainDetail = true;
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $detail = $this->setDetail($variant, $needsOptionUpdate);
            if ($detail === null) continue;

            $detail->setKind(2);
            if ($isMainDetail) {
                $detail->setKind(1);
                $article->setMainDetail($detail);
                $isMainDetail = false;
            }
            // set 'mxc_flavor' attribute if not empty (used in frontend product lists)
            if (! empty($flavor)) {
                ArticleTool::setDetailAttribute($detail, 'mxc_flavor', $flavor);
            }
        }
    }

    /**
     * Set the properties of the Shopware detail associated to the given InnoCigs variant.
     * If the detail does not exist, it will be created.
     *
     * @param Variant $variant
     * @param bool $needsOptionUpdate
     * @return Detail|null
     */
    public function setDetail(Variant $variant, bool $needsOptionUpdate)
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
            if ($needsOptionUpdate) {
                $configuratorOptions = $detail->getConfiguratorOptions();
                $configuratorOptions->clear();
                $detail->setConfiguratorOptions(new ArrayCollection($variant->getShopwareOptions()));
            }
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

        $this->setShopwareDetailProperties($variant);

        // All valid details are marked active and lastStock
        $detail->setActive(true);
        $detail->setLastStock(1);

        // set next two properties only on detail creation
        $this->priceMapper->setRetailPrices($variant);

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
        if (!$detail) return;

        $detail->setNumber($variant->getNumber());
        $detail->setEan($variant->getEan());
        $detail->setPurchasePrice($variant->getPurchasePrice());
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
        ArticleTool::deleteArticle($article);

        $configuratorSetName = 'mxc-set-' . $product->getIcNumber();
        if ($set = $this->getSetRepository()->findOneBy(['name' => $configuratorSetName]))
        {
            $this->modelManager->remove($set);
        }

        $product->setArticle(null);
        $product->setActive(false);
        $product->setLinked(false);
        $this->modelManager->flush();
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