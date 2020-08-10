<?php

namespace MxcDropshipIntegrator\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipIntegrator\Toolbox\Models\PrimaryKeyTrait;
use MxcDropshipIntegrator\Toolbox\Models\TrackCreationAndUpdateTrait;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_option")
 * @ORM\Entity(repositoryClass="OptionRepository")
 */
class Option extends ModelEntity  {

    use PrimaryKeyTrait;
    use TrackCreationAndUpdateTrait;

    /**
     * @var string $name
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var Group $icGroup
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="options")
     */
    private $icGroup;

    /**
     * @var bool $accepted
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $accepted;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Variant", mappedBy="options")
     */
    private $variants;

    /** @var boolean $valid */
    private $valid;

    public function __construct() {
        $this->variants = new ArrayCollection();
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
     * @return Group
     */
    public function getIcGroup(): Group
    {
        return $this->icGroup;
    }

    /**
     * @param null|Group $icGroup
     */
    public function setIcGroup(?Group $icGroup)
    {
        $this->icGroup = $icGroup;
    }

    /**
     * @return ArrayCollection
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * @param Variant $variant
     *
     * This is the 'owned' side, so we DO NOT $variant->addOption($this)
     */
    public function addVariant(Variant $variant)
    {
        $this->variants->add($variant);
    }

    public function removeVariant(Variant $variant) {
        $this->variants->removeElement($variant);
    }
    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    public function getAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * @param bool $accepted
     */
    public function setAccepted(bool $accepted)
    {
        $this->accepted = $accepted;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (! $this->valid) {
            $this->valid = $this->accepted && $this->getIcGroup()->isAccepted();
        }
        return $this->valid;
    }
}