<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_master_import")
 */
class Article extends ModelEntity  {

    use BaseModelTrait;

    /**
     * @var string $number
     * @ORM\Column()
     */
    private $number;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="Variant", mappedBy="master", cascade={"persist", "remove"})
     */
    private $models;


    public function __construct() {
        $this->models = new ArrayCollection();
    }

    public function setModels(ArrayCollection $models) {
        $this->models = $models;
    }

    public function getModels() {
        return $this->models;
    }

    /**
     * @param Variant $model
     */
    public function addModel(Variant $model) {
        $this->models->add($model);
        $model->setMaster($this);
    }
}