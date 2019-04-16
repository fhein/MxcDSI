<?php


namespace MxcDropshipInnocigs\Mapping\Shopware;


use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;
use Shopware\Models\Article\Article;

class ImageMapper
{
    /** @var LoggerInterface $log */
    protected $log;

    /** @var MediaTool mediaTool */
    protected $mediaTool;

    /** @var ArrayCollection $articleImages */
    protected $articleImages;

    /** @var array $mainImages */
    protected $mainImages;

    public function __construct(MediaTool $mediaTool, LoggerInterface $log)
    {
        $this->log = $log;
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

    public function setDetailImages(Variant $variant, Article $article)
    {
        $detail = $variant->getDetail();
        if ($detail === null) {
            return;
        }

        $i = count($this->mainImages) + 1;
        $icImages = $variant->getImages();
        foreach ($icImages as $icImage) {
            $image = $this->mainImages[$icImage->getUrl()];

            if (null === $image) {
                $this->log->debug($icImage->getUrl());
                $image = $this->mediaTool->getImage($icImage->getUrl(), $article, $i++); //entry for Image itself
                $this->articleImages->add($image);
                $this->mainImages[$icImage->getUrl()] = $image;
            }

            if ($detail->getConfiguratorOptions() !== null) {
                $this->mediaTool->setOptionMappings($detail->getConfiguratorOptions(), $image);
            }

            $detailImg = $this->mediaTool->createDetailImage($icImage->getUrl(), $detail);   //image entry for detail relation
            $detailImg->setParent($image);
            $detailImg->setMain($image->getMain());
            $detailImg->setPosition($image->getPosition());
            $this->articleImages->add($detailImg);
        }
    }
}