<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_group")
 * @ORM\Entity(repositoryClass="GroupRepository", readOnly=true)
 */
class Group extends ModelEntity  {

    use BaseModelTrait;

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
     * @ORM\OneToMany(targetEntity="Option", mappedBy="icGroup", cascade={"persist", "remove"})
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

    // This API gets implicitly called when the user saves an article.
    //
    // If the 'Save' button gets clicked on the article detail window
    // updated variant information is provided (accepted status of each variant).
    //
    // If the 'Save' action gets triggered via the article listing
    // (cell editing, 'Activate selected', etc), an empty variant array
    // is provided.
    //
    // So we apply the variant array only if it is not empty. Otherwise
    // the variants, which are all well defined and present, would be removed.
    //
    public function setOptions($options) {
        if (! empty($options)) {
            $this->setOneToMany($options, 'MxcDropshipInnocigs\Models\Option', 'options');
        }
    }

    /**
     * @return Collection
     */
    public function getOptions(): Collection
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