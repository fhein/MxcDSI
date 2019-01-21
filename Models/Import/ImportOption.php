<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_option_import")
 */
class ImportOption extends ModelEntity  {

    use BaseModelTrait;

    /**
     * @var string $name
     *
     * @ORM\Column()
     */
    private $name;

    /**
     * @var ImportGroup $icGroup
     * @ORM\ManyToOne(targetEntity="ImportGroup", inversedBy="options")
     */
    private $icGroup;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Variant", inversedBy="options")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_import_options_models")
     */
    private $models;

    public function __construct() {
        $this->models = new ArrayCollection();
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
     * @return ImportGroup
     */
    public function getIcGroup()
    {
        return $this->icGroup;
    }

    /**
     * @param ImportGroup $icGroup
     */
    public function setIcGroup(ImportGroup $icGroup)
    {
        $this->icGroup = $icGroup;
    }

    /**
     * @return ArrayCollection
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * @param ImportVariant $model
     *
     * This is the 'owned' side, so we DO NOT $variant->addOption($this)
     */
    public function addModel(ImportVariant $model)
    {
        $this->models->add($model);
    }
}