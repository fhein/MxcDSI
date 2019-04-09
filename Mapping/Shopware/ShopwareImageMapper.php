<?php


namespace MxcDropshipInnocigs\Mapping\Shopware;


use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\Media\MediaTool;
use Shopware\Models\Article\Article as ShopwareArticle;

class ShopwareImageMapper
{
    /** @var LoggerInterface $log */
    protected $log;

    /** @var MediaTool mediaTool */
    protected $mediaTool;

    /** @var ArrayCollection $shopwareArticleImages */
    protected $shopwareArticleImages;

    /** @var array $shopwareMainImages */
    protected $shopwareMainImages;

    public function __construct(MediaTool $mediaTool, LoggerInterface $log)
    {
        $this->log = $log;
        $this->mediaTool = $mediaTool;
    }

    public function setArticleImages(Article $icArticle)
    {
        /** @var ShopwareArticle $swArticle */
        $swArticle = $icArticle->getArticle();
        if (!$swArticle) {
            return;
        }

        $this->shopwareMainImages = [];
        $this->shopwareArticleImages = new ArrayCollection();

        $this->mediaTool->removeImages($swArticle);

        $variants = $icArticle->getVariants();
        foreach ($variants as $variant) {
            $this->setDetailImages($variant, $swArticle);
        }
        $swArticle->setImages($this->shopwareArticleImages->toArray());
    }

    public function setDetailImages(Variant $variant, ShopwareArticle $swArticle)
    {
        $swDetail = $variant->getDetail();
        if ($swDetail === null) {
            return;
        }

        $i = count($this->shopwareMainImages) + 1;
        $icImages = $variant->getImages();
        foreach ($icImages as $icImage) {
            $image = $this->shopwareMainImages[$icImage->getUrl()];

            if (null === $image) {
                $this->log->debug($icImage->getUrl());
                $image = $this->mediaTool->getImage($icImage->getUrl(), $swArticle, $i++); //entry for Image itself
                $this->shopwareArticleImages->add($image);
                $this->shopwareMainImages[$icImage->getUrl()] = $image;
            }

            if ($swDetail->getConfiguratorOptions() !== null) {
                $this->mediaTool->setOptionMappings($swDetail->getConfiguratorOptions(), $image);
            }

            $detailImg = $this->mediaTool->createDetailImage($icImage->getUrl(), $swDetail);   //image entry for detail relation
            $detailImg->setParent($image);
            $detailImg->setMain($image->getMain());
            $detailImg->setPosition($image->getPosition());
            $this->shopwareArticleImages->add($detailImg);
        }
    }
}