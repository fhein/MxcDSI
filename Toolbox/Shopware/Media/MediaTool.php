<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware\Media;

use DateTime;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Image;
use Shopware\Models\Media\Album;
use Shopware\Models\Media\Media;
use Shopware_Components_Auth;

class MediaTool implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

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
     */
    public function __construct() {
        $container = Shopware()->Container();
        $this->authService = $container->get('Auth');
        $this->mediaService = $container->get('shopware_media.media_service');
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

        /** @var Album $album */
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
     * @param Article $article
     * @param int $position
     * @return Image
     */
    public function getImage(string $url, Article $article, int $position):Image
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

        $image->setArticle($article);
        $image->setMedia($media);
        $image->setExtension($urlInfo['extension']);
        $image->setMain(($position > 1) ? 2 : 1);
        $image->setPath($media->getName());
        $image->setPosition($position);

        // Important to avoid 'A new entity was detected which is not configured to cascade persist'
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush($image);
        return $image;

    }

    public function createDetailImage(string $url, $detail) {
        $urlInfo = pathinfo($url);

        $image = new Image();
        $this->modelManager->persist($image);

        $image->setExtension($urlInfo['extension']);
        $image->setArticleDetail($detail);

        return $image;
    }

    public function setOptionMappings($configuratorOptions, Image $image){

        if ($configuratorOptions !== null) {

            $mapping = new Image\Mapping();
            $this->modelManager->persist($mapping);

            $rules = $mapping->getRules();
            /** @var Option $option */
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

    public function removeImages(Article $article)
    {
        $images = $article->getImages();
        foreach ($images as $image) {
            $this->removeImage($image);
        }
        $images->clear();
    }
}
