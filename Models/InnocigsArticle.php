<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_mxc_dropship_innocigs_article")
 */
class InnocigsArticle extends ModelEntity {
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
     * @var string $innocigsName
     *
     * @ORM\Column(name="innocigs_name")
     */
    private $innocigsName;

    /**
     * @var string $name
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var string $innocigsCode
     *
     * @ORM\Column(name="innocigs_code", type="string", nullable=false)
     */
    private $innocigsCode;

    /**
     * @var string $code
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $code;

    /**
     * @var string $description
     *
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="InnocigsVariant",
     *      mappedBy="article",
     *      cascade={"persist", "remove"}
     * )
     */
    private $variants;

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
     * @var \DateTime $created
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $created = null;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $updated = null;

    public function __construct() {
        $this->variants = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active)
    {
        $this->active = $active;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getVariants(): ArrayCollection
    {
        return $this->variants;
    }

    /**
     * @param InnocigsVariant $variant
     */
    public function addVariant(InnocigsVariant $variant) {
        $this->variants->add($variant);
        $variant->setArticle($this);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code)
    {
        $this->code = $code;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return bool
     */
    public function isIgnored(): bool
    {
        return $this->ignored;
    }

    /**
     * @return bool
     */
    public function getIgnored(): bool
    {
        return $this->ignored;
    }

    /**
     * @param bool $ignored
     */
    public function setIgnored(bool $ignored)
    {
        $this->ignored = $ignored;
    }

    /**
     * @return string
     */
    public function getInnocigsName(): string
    {
        return $this->innocigsName;
    }

    /**
     * @param string $innocigsName
     */
    public function setInnocigsName(string $innocigsName): void
    {
        $this->innocigsName = $innocigsName;
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
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}