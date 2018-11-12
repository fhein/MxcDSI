<?php

namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
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
     * @var string $supplier
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $supplier;

    /**
     * @var string $brand
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $brand;

    /**
     * @var string $description
     *
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @var string $image;
     *
     * @ORM\Column(name="image", type="string", nullable=false)
     */
    private $image;

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
     * @var DateTime $created
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created = null;

    /**
     * @var int $configSetId
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $configSetId = null;

    /**
     * @var DateTime $updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated = null;

    public function __construct() {
        $this->variants = new ArrayCollection();
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
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
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

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * @return int
     */
    public function getConfigSetId(): int
    {
        return $this->configSetId;
    }

    /**
     * @param int $configSetId
     */
    public function setConfigSetId(int $configSetId): void
    {
        $this->configSetId = $configSetId;
    }

    /**
     * Build up an array which maps attributes to variants by group
     *
     *  [ <attribute group name> => [
     *        <attribute name> => [
     *              'variant' => <variant>,
     *        ],
     *        ...
     *     ],
     *    ...
     *  ]
     *
     * @return array
     */
    public function getVariantsByGroupAndOption() {
        $map = [];
        $variants = $this->getVariants();
        foreach($variants as $variant) {
            /**
             * @var InnocigsVariant $variant
             */
            $attributes = $variant->getAttributes();
            foreach ($attributes as $attribute) {
                /**
                 * @var InnocigsAttribute $attribute
                 */
                $group = $attribute->getAttributeGroup();
                $map[$group->getName()][$attribute->getName()] = [
                    'variant' => $variant,
                ];
            }
        }
        return $map;
    }

    /**
     * @return string
     */
    public function getSupplier(): string
    {
        return $this->supplier;
    }

    /**
     * @param string $supplier
     */
    public function setSupplier(string $supplier): void
    {
        $this->supplier = $supplier;
    }

    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }
}