<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware\Media;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Image;
use Shopware\Models\Media\Album;
use Shopware\Models\Media\Media;
use Shopware_Components_Auth;
use Traversable;

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
        $this->init();
    }

    public function init() {
        $this->shopwareArticleImages = new ArrayCollection();
        $this->shopwareMainImages = [];
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
     * @param Article $swArticle
     * @param int $position
     * @return Image
     */
    protected function getImage(string $url, Article $swArticle, int $position):Image
    {
        $urlInfo = pathinfo($url);
        $swUrl = 'media/image/' . $urlInfo['basename'];

        if (!$this->mediaService->has($swUrl)) {
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

    protected function createDetailImage(string $url, $swDetail, int $position) {
        $urlInfo = pathinfo($url);

        $image = new Image();
        $this->modelManager->persist($image);

        $image->setExtension($urlInfo['extension']);
        $image->setMain(($position > 1) ? 2 : 1);
        $image->setPosition($position);
        $image->setArticleDetail($swDetail);

        return $image;
    }

    public function setArticleImages($icImages, Article $swArticle, Detail $swDetail)
    {
        if (is_string($icImages)) {
            $icImages = [ $icImages ];
        }
        if (! (is_array($icImages) || $icImages instanceof Traversable)) {
            throw new InvalidArgumentException(
                sprintf('Invalid argument supplied: Expected string, array or instance of Traversable, got %s.',
                is_object($icImages) ? get_class($icImages) : gettype($icImages)
                )
            );
        }

        $i=count($this->shopwareMainImages) +1;
        foreach ($icImages as $icImage) {

            $image = $this->shopwareMainImages[$icImage->getUrl()];

            if (null === $image) {
                $image = $this->getImage($icImage->getUrl(), $swArticle, $i); //entry for Image itself
                $this->shopwareArticleImages->add($image);
            }

            if ($swDetail->getConfiguratorOptions() !== null) $this->setOptionMappings($swDetail->getConfiguratorOptions(), $image);

            $detailImg = $this->createDetailImage($icImage->getUrl(), $swDetail, $image->getPosition()); //image entry for detail relation
            $detailImg->setParent($image);
            $detailImg->setMain($image->getMain());

            $this->shopwareArticleImages->add($detailImg);
            $this->shopwareMainImages[$icImage->getUrl()] = $image;

            $i++;
        }
        $swArticle->setImages($this->shopwareArticleImages);

        return $this->shopwareArticleImages;
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
}
