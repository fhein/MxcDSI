<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity(repositoryClass="InnocigsAttributeGroupRepository")
 * @ORM\Table(name="s_plugin_mxc_dropship_innocigs_attribute_group")
 */
class InnocigsAttributeGroup extends ModelEntity {
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
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="MxcDropshipInnocigs\Models\InnocigsAttribute",
     *      mappedBy="attributeGroup",
     *      cascade={"persist", "remove"}
     * )
     */
    private $attributes;

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
        $this->attributes = new ArrayCollection();
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
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAttributes(): ArrayCollection
    {
        return $this->attributes;
    }

    /**
     * @param InnocigsAttribute $attribute
     */
    public function addAttribute(InnocigsAttribute $attribute) {
        $this->attributes->add($attribute);
        $attribute->setAttributeGroup($this);
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