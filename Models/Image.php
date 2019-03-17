<?php /** @noinspection PhpUnhandledExceptionInspection */
namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_image")
 * @ORM\Entity(repositoryClass="ImageRepository")
 */
class Image extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Variant", mappedBy="images")
     */
    private $variants;

    /**
     * @var string $url
     * @ORM\Column(type="string", nullable=false)
     */
    private $url;

    /**
     * @var bool $accepted
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $accepted;

    public function __construct()
    {
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

    /**
     * @return Collection
     */
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

    /**
     * @param Variant $variant
     */
    public function addVariant(Variant $variant) {
        $this->variants->add($variant);
    }

    public function removeVariant(Variant $variant)
    {
        $this->variants->removeElement($variant);
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