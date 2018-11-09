<?php

namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dropship_innocigs_variant")
 */
class InnocigsVariant extends ModelEntity
{
    /**
     * Primary Key - autoincrement value
     *
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var InnocigsArticle $article
     * @ORM\ManyToOne(targetEntity="InnocigsArticle", inversedBy="variants")
     */
    private $article;

    /**
     * @var string $innocigsCode
     *
     * @ORM\Column(name="innocigs_code", type="string", nullable=false)
     */
    private $innocigsCode;

    /**
     * @var string $code
     *
     * @ORM\Column(name="code", type="string", nullable=false)
     */
    private $code;

    /**
     * @var string $ean
     *
     * @ORM\Column(name="ean", type="string", nullable=false)
     */
    private $ean;

    /**
     * @var float $priceNet
     *
     * @ORM\Column(name="price_net", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $priceNet;

    /**
     * @var float $priceRecommended
     *
     * @ORM\Column(name="price_rcmd", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $priceRecommended;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany( targetEntity="MxcDropshipInnocigs\Models\InnocigsAttribute", mappedBy="variants")
     */
    private $attributes;

    /**
     * @var boolean $active
     *
     * @ORM\Column(type="boolean")
     */
    private $active = false;

    /**
     * @var boolean $ignored
     *
     * @ORM\Column(type="boolean")
     */
    private $ignored = true;

    /**
     * @var int $detailId
     * @ORM\Column($ype="integer", nullable=true)
     */
    private $detailId = null;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created = null;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated = null;

    public function __construct() {
        $this->attributes = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps() {
        $now = new DateTime();
        $this->updated = $now;
        if ( null === $this->created) {
            $this->created = $now;
        }
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAttributes()//: ArrayCollection
    {
        return $this->attributes;
    }

    /**
     * @param InnocigsAttribute $attribute
     *
     * This is the owner side so we have to add the backlink here
     */
    public function addAttribute(InnocigsAttribute $attribute) {
        $this->attributes->add($attribute);
        $attribute->addVariant($this);
    }

    /**
     * @return \DateTime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return InnocigsArticle $article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @return string $code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string $ean
     */
    public function getEan()
    {
        return $this->ean;
    }

    /**
     * @return float $priceNet
     */
    public function getPriceNet()
    {
        return $this->priceNet;
    }

    /**
     * @return float $priceRecommended
     */
    public function getPriceRecommended()
    {
        return $this->priceRecommended;
    }

    /**
     * @return bool $active
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param InnocigsArticle $article
     */
    public function setArticle(InnocigsArticle $article)
    {
        $this->article = $article;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @param string $ean
     */
    public function setEan($ean)
    {
        $this->ean = $ean;
    }

    /**
     * @param float $priceNet
     */
    public function setPriceNet($priceNet)
    {
        $this->priceNet = $priceNet;
    }

    /**
     * @param float $priceRecommended
     */
    public function setPriceRecommended($priceRecommended)
    {
        $this->priceRecommended = $priceRecommended;
    }

    /**
     * @return bool
     */
    public function isIgnored(): bool
    {
        return $this->ignored;
    }

    /**
     * @param bool $ignored
     */
    public function setIgnored(bool $ignored): void
    {
        $this->ignored = $ignored;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getInnocigsCode(): string
    {
        return $this->innocigsCode;
    }

    /**
     * @param string $innocigsCode
     */
    public function setInnocigsCode(string $innocigsCode): void
    {
        $this->innocigsCode = $innocigsCode;
    }

    /**
     * @return int
     */
    public function getDetailId(): int
    {
        return $this->detailId;
    }

    /**
     * @param int $detailId
     */
    public function setDetailId(int $detailId): void
    {
        $this->detailId = $detailId;
    }
}
