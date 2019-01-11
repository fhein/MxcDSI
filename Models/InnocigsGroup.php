<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dropship_configurator_group")
 */
class InnocigsGroup extends ModelEntity  {
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
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="InnocigsOption",
     *      mappedBy="innocigsGroup",
     *      cascade={"persist", "remove"}
     * )
     */
    private $options;

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
        $this->options = new ArrayCollection();
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
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
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
            $this->setOneToMany($options, 'MxcDropshipInnocigs\Models\InnocigsOption', 'options');
        }
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @param InnocigsOption $option
     */
    public function addOption(InnocigsOption $option) {
        $this->options->add($option);
        $option->setInnocigsGroup($this);
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