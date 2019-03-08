<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware\Media;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as ShopwareArticle;
use Shopware\Models\Article\Image;
use Shopware\Models\Media\Album;
use Shopware\Models\Media\Media;
use Shopware_Components_Auth;

class MediaTool
{
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;
    /**
     * @var LoggerInterface $log
     */
    protected $log;
    /**
     * @var Shopware_Components_Auth $authService
     */
    protected $authService;
    /**
     * @var MediaService $mediaService
     */
    protected $mediaService;

    /**
     * @var ArrayCollection $shopwareArticleImages
     */
    protected $shopwareArticleImages;

    /**
     * @var array $shopwareMainImages
     */
    protected $shopwareMainImages;

    /**
     * MediaService constructor.
     *
     * @param ModelManager $modelManager
     * @param MediaService $mediaService
     * @param Shopware_Components_Auth $authService
     * @param LoggerInterface $log
     */
    public function __construct(ModelManager $modelManager, MediaService $mediaService, Shopware_Components_Auth $authService, LoggerInterface $log) {
        $this->modelManager = $modelManager;
        $this->authService = $authService;
        $this->mediaService = $mediaService;
        $this->log = $log;
    }

    protected function getMedia(string $swUrl, string $url){

        $media = $this->modelManager->getRepository(Media::class)->findOneBy(['path' => $swUrl]);
        if (null === $media) {
            $media = $this->createMedia($swUrl, $url);
        }
        $media->loadThumbnails();

        return $media;
    }

    protected function createMedia (string $swUrl, string $url ){
        $urlInfo = pathinfo($url);

        $album = $this->modelManager->getRepository(Album::class)->findOneBy(['id' => -1]);

        $media = new Media();
        $this->modelManager->persist($media);

        $media->setAlbumId(-1);
        $media->setAlbum($album);
        $media->setName($urlInfo['filename']);
        $media->setPath($swUrl);
        $media->setType('IMAGE');
        $media->setExtension($urlInfo['extension']);
        $media->setDescription('');
        $media->setUserId($this->authService->getIdentity()->id);
        /** @noinspection PhpUnhandledExceptionInspection */
        $media->setCreated(new DateTime());
        $media->setFileSize(filesize($url));

        $size = getimagesize($url);
        $size = (false === $size) ? [ 0, 0 ] : $size;
        $media->setWidth($size{0});
        $media->setHeight($size{1});

        return $media;
    }

    /**
     * Downloads image from given url if the image is not present already
     *
     * @param string $url
     * @param ShopwareArticle $swArticle
     * @param int $position
     * @return Image
     */
    protected function getImage(string $url, ShopwareArticle $swArticle, int $position):Image
    {
        $urlInfo = pathinfo($url);
        $swUrl = 'media/image/' . $urlInfo['basename'];

        if (! $this->mediaService->has($swUrl)) {
            $this->log->debug('Downloading image from ' . $url);
            $fileContent = file_get_contents($url);

            // save to filesystem
            $this->log->debug('Saving image to ' . $swUrl);
            $this->mediaService->write($swUrl, $fileContent);
        } else {
            $this->log->debug('Media service already has image ' . $swUrl);
        }

        $media = $this->getMedia($swUrl, $url);

        $image = new Image();
        $this->modelManager->persist($image);

        $image->setArticle($swArticle);
        $image->setMedia($media);
        $image->setExtension($urlInfo['extension']);
        $image->setMain(($position > 1) ? 2 : 1);
        $image->setPath($media->getName());
        $image->setPosition($position);

        return $image;

    }

    protected function createDetailImage(string $url, $swDetail) {
        $urlInfo = pathinfo($url);

        $image = new Image();
        $this->modelManager->persist($image);

        $image->setExtension($urlInfo['extension']);
        $image->setArticleDetail($swDetail);

        return $image;
    }

    public function setArticleImages(Article $icArticle, ShopwareArticle $swArticle) {
        $this->shopwareMainImages = [];
        $this->shopwareArticleImages = new ArrayCollection();

        $this->removeImages($swArticle);

        $variants = $icArticle->getVariants();
        foreach ($variants as $variant) {
            $this->setDetailImages($variant, $swArticle);
        }
        $swArticle->setImages($this->shopwareArticleImages);
    }

    public function setDetailImages(Variant $variant, ShopwareArticle $swArticle)
    {
        $swDetail = $variant->getDetail();
        if ($swDetail === null) return;

        $i=count($this->shopwareMainImages) + 1;
        $icImages = $variant->getImages();
        foreach ($icImages as $icImage) {
            $image = $this->shopwareMainImages[$icImage->getUrl()];

            if (null === $image) {
                $image = $this->getImage($icImage->getUrl(), $swArticle, $i++); //entry for Image itself
                $this->shopwareArticleImages->add($image);
                $this->shopwareMainImages[$icImage->getUrl()] = $image;
            }

            if ($swDetail->getConfiguratorOptions() !== null) {
                $this->setOptionMappings($swDetail->getConfiguratorOptions(), $image);
            }

            $detailImg = $this->createDetailImage($icImage->getUrl(), $swDetail); //image entry for detail relation
            $detailImg->setParent($image);
            $detailImg->setMain($image->getMain());
            $detailImg->setPosition($image->getPosition());
            $this->shopwareArticleImages->add($detailImg);
        }
    }

    protected function setOptionMappings($configuratorOptions, Image $image){

        if ($configuratorOptions !== null) {

            $mapping = new Image\Mapping();
            $this->modelManager->persist($mapping);

            $rules = $mapping->getRules();

            foreach ($configuratorOptions as $option) {
                $rule = new Image\Rule();
                $this->modelManager->persist($rule);
                $rule->setOption($option);
                $rule->setMapping($mapping);
                $rules->add($rule);
            }

            $mapping->setImage($image);
            $mapping->setRules($rules);
            /** @noinspection PhpParamsInspection */
            $image->setMappings([$mapping]);
        }
    }

    protected function removeImage(Image $image)
    {
        $children = $image->getChildren();
        foreach ($children as $child) {
            $this->removeImage($child);
        }
        $children->clear();

        $mappings = $image->getMappings();
        /** @var Image\Mapping $mapping */
        foreach ($mappings as $mapping) {
            $rules = $mapping->getRules();
            foreach ($rules as $rule) {
                $this->modelManager->remove($rule);
            }
            $this->modelManager->remove($mapping);
        }
        $this->modelManager->remove($image);
    }

    protected function removeImages(ShopwareArticle $swArticle)
    {
        $images = $swArticle->getImages();
        foreach ($images as $image) {
            $this->removeImage($image);
        }
        $images->clear();
    }
}
