<?php

namespace MxcDropshipInnocigs\Toolbox\Media;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
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
     * @return Image
     */
    protected function getImage(string $url) {
        $urlInfo = pathinfo($url);
        $swUrl = 'media/image/'.$urlInfo['basename'];

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

        $image->setMedia($media);
        $image->setExtension($urlInfo['extension']);
        $image->setMain(1);
        $image->setPath($media->getName());
        $image->setPosition(1);

        return $image;
    }

    public function setArticleImages($images, Article $swArticle)
    {
        if (is_string($images)) {
            $images = [ $images ];
        }
        if (! (is_array($images) || $images instanceof Traversable)) {
            throw new InvalidArgumentException(
                sprintf('Invalid argument supplied: Expected string, array or instance of Traversable, got %s.',
                is_object($images) ? get_class($images) : gettype($images)
                )
            );
        }
        $imageCollection = new ArrayCollection();
        foreach ($images as $url) {
            $image = $this->getImage($url);
            $image->setArticle($swArticle);
            $this->modelManager->persist($image);
            $imageCollection->add($image);
        }
        $swArticle->setImages($imageCollection);
    }
}
