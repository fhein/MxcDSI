<?php /** @noinspection PhpUnhandledExceptionInspection */
namespace MxcDropshipInnocigs\Models\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_image_import")
 */
class ImportImage extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var string $image
     * @ORM\Column()
     */
    private $image;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Variant", inversedBy="addtionalImages")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_import_images_models")
     */
    private $models;

    public function __construct() {
        $this->models = new ArrayCollection();
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
    public function setImage(string $image)
    {
        $this->image = $image;
    }

    public function getModels()
    {
        return $this->models;
    }

    /**
     * @param ArrayCollection $models
     */
    public function setModels(ArrayCollection $models)
    {
        $this->models = $models;
    }

    public function addModel(ImportVariant $model) {
        $this->models->add($model);
    }
}