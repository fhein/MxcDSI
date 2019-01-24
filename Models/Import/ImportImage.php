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
 * @ORM\Entity(repositoryClass="ImportImageRepository")
 */
class ImportImage extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var string $url
     * @ORM\Column()
     */
    private $url;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="ImportVariant", mappedBy="additionalImages")
     */
    private $variants;

    public function __construct() {
        $this->variants = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
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