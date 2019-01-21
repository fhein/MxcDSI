<?php /** @noinspection PhpUnhandledExceptionInspection */
namespace MxcDropshipInnocigs\Models\Work;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_image")
 */
class Image extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var Variant $variant
     * @ORM\ManyToOne(targetEntity="Variant", inversedBy="images")
     */
    private $variant;

    /**
     * @var string $image
     *
     * @ORM\Column()
     */
    private $image;

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

    /**
     * @return Variant
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * @param Variant $variant
     */
    public function setVariant(Variant $variant): void
    {
        $this->variant = $variant;
    }
}