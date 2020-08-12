<?php

namespace MxcDropshipIntegrator\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MxcCommons\Toolbox\Models\PrimaryKeyTrait;
use MxcCommons\Toolbox\Models\TrackCreationAndUpdateTrait;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_group")
 * @ORM\Entity(repositoryClass="GroupRepository")
 */
class Group extends ModelEntity
{
    use PrimaryKeyTrait;
    use TrackCreationAndUpdateTrait;

    /**
     * @var string $name
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var bool $accepted
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $accepted;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Option", mappedBy="icGroup", cascade={"persist","remove"})
     */
    private $options;

    public function __construct() {
        $this->options = new ArrayCollection();
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

    public function setOptions($options) {
        $this->setOneToMany($options, 'MxcDropshipIntegrator\Models\Option', 'options');
    }

    /**
     * @return Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Option $option
     */
    public function addOption(Option $option) {
        $this->options->add($option);
        $option->setIcGroup($this);
    }

    public function removeOption(Option $option)
    {
        $this->options->removeElement($option);
        $option->setIcGroup(null);
    }

    /**
     * @return bool
     */
    public function isAccepted(): bool
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
}