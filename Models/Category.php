<?php

namespace MxcDropshipIntegrator\Models;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipIntegrator\Toolbox\Models\PrimaryKeyTrait;
use MxcDropshipIntegrator\Toolbox\Models\TrackCreationAndUpdateTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity(repositoryClass="CategoryRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_category")
 */
class Category extends ModelEntity  {

    use PrimaryKeyTrait;
    use TrackCreationAndUpdateTrait;

    /** @ORM\Column(type="string", nullable=false) */
    private $path;

    /** @ORM\Column(type="string", nullable=true) */
    private $h1;

    /** @ORM\Column(type="string", nullable=true) */
    private $title;

    /** @ORM\Column(type="string", nullable=true) */
    private $description;

    /** @ORM\Column(type="string", nullable=true) */
    private $keywords;

    /** @ORM\Column(type="string", nullable=true) */
    private $url;

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getH1()
    {
        return $this->h1;
    }

    /**
     * @param mixed $h1
     */
    public function setH1($h1)
    {
        $this->h1 = $h1;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param mixed $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}