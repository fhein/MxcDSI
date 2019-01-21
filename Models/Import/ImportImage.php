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
     * @ORM\ManyToMany(targetEntity="ImportVariant", inversedBy="additionalImages")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_import_images_variants")
     */
    private $variants;

    public function __construct() {
        $this->variants = new ArrayCollection();
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

    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * @param ArrayCollection $variants
     */
    public function setVariants(ArrayCollection $variants)
    {
        $this->variants = $variants;
    }

    public function addVariant(ImportVariant $variant) {
        $this->variants->add($variant);
    }
}