<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcDropshipInnocigs\Mapping\Shopware;


use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\MediaTool;
use Shopware\Models\Article\Article;

class ImageMapper implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var MediaTool mediaTool */
    protected $mediaTool;

    /** @var ArrayCollection $articleImages */
    protected $articleImages;

    /** @var array $mainImages */
    protected $mainImages;

    public function __construct(MediaTool $mediaTool)
    {
        $this->mediaTool = $mediaTool;
    }

    public function setArticleImages(Product $product)
    {
        /** @var Article $article */
        $article = $product->getArticle();
        if (!$article) return;

        $this->mainImages = [];
        $this->articleImages = new ArrayCollection();

        $this->mediaTool->removeImages($article);

        $variants = $product->getVariants();
        foreach ($variants as $variant) {
            $this->setDetailImages($variant, $article);
        }
        $article->setImages($this->articleImages->toArray());
    }

    protected function setDetailImages(Variant $variant, Article $article)
    {
        $detail = $variant->getDetail();
        if ($detail === null) return;

        $i = count($this->mainImages) + 1;
        $icImageUrls = explode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $variant->getImages());
        foreach ($icImageUrls as $icImageUrl) {
            if (empty($icImageUrl)) continue;
            $image = $this->mainImages[$icImageUrl];

            if (null === $image) {
                $image = $this->mediaTool->getImage($icImageUrl, $i++);
                $image->setArticle($article);
                $this->articleImages->add($image);
                $this->mainImages[$icImageUrl] = $image;
            }

            if ($detail->getConfiguratorOptions() !== null) {
                $this->mediaTool->setOptionMappings($detail->getConfiguratorOptions(), $image);
            }

            $detailImg = $this->mediaTool->createDetailImage($icImageUrl, $detail);   //image entry for detail relation
            $detailImg->setParent($image);
            $detailImg->setMain($image->getMain());
            $detailImg->setPosition($image->getPosition());
            // @todo: check if the next line is required
            // $this->modelManager->flush($detailImg);
            // if the detailimage is added to an article, the article id is written into the data record. In this case, the mapping to the article detail gets lost when the article is saved again via shopware backend
            //$this->articleImages->add($detailImg);
        }
    }
}