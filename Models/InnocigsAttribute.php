<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_mxc_dropship_innocigs_attribute")
 */
class InnocigsAttribute extends ModelEntity {
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
     * @var InnocigsAttributeGroup $attributeGroup
     * @ORM\ManyToOne(targetEntity="MxcDropshipInnocigs\Models\InnocigsAttributeGroup", inversedBy="attributes")
     */
    private $attributeGroup;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany( targetEntity="InnocigsVariant", inversedBy="attributes")
     * @ORM\JoinTable(name="s_plugin_mxc_dropship_innocigs_attributes_variants")
     */
    private $variants;

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
     * @return InnocigsAttributeGroup
     */
    public function getAttributeGroup(): InnocigsAttributeGroup
    {
        return $this->attributeGroup;
    }

    /**
     * @param InnocigsAttributeGroup $attributeGroup
     */
    public function setAttributeGroup(InnocigsAttributeGroup $attributeGroup): void
    {
        $this->attributeGroup = $attributeGroup;
    }

    /**
     * @return ArrayCollection
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * @param InnocigsVariant $variant
     *
     * This is the 'owned' side, so we DO NOT $variant->addAttribute($this)
     */
    public function addVariant(InnocigsVariant $variant): void
    {
        $this->variants->add($variant);
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
}