<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Article\Configurator\Option as ShopwareOption;
use Shopware\Models\Article\Detail;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_variant")
 * @ORM\Entity(repositoryClass="VariantRepository")
 */
class Variant extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var Article $article
     *
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="variants")
     */
    private $article;

    /**
     * @var Detail
     */
    private $detail;

    /**
     * @var string $number
     * @ORM\Column(name="number", type="string", nullable=false)
     */
    private $number;

    /**
     * @var string $number
     * @ORM\Column(name="ic_number", type="string", nullable=false)
     */
    private $icNumber;

    /**
     * @var string $ean
     *
     * @ORM\Column(name="ean", type="string", nullable=true)
     */
    private $ean;
    /**
     * @var float $purchasePrice
     * @ORM\Column(name="purchase_price", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $purchasePrice;

    /**
     * @var float $retailPrice
     *
     * @ORM\Column(name="retail_price", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $retailPrice;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Option", inversedBy="variants")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_variants_options")
     */
    private $options;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Image", inversedBy="variants", cascade="persist")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_variants_images")
     */
    private $images;

    /**
     * @var @ORM\Column(type="string", nullable=true)
     */
    private $description;

    /**
     * @var boolean $active
     *
     * @ORM\Column(type="boolean")
     */
    private $active = false;

    /**
     * @var boolean $accepted
     *
     * @ORM\Column(type="boolean")
     */
    private $accepted = true;

    /**
     * @var boolean $new
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $new = true;


    /**
     * @var array $shopwareOptions
     *
     * This property will not be persisted. The array gets filled by
     * ArticleOptionMapper, which creates Shopware options from our
     * options and adds the created shopware options here.
     *
     * Later on, the ArticleMapper will create the shopware detail
     * records, which get associations to the shopware options stored here.
     */
    private $shopwareOptions = [];

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Option $option./in
     *
     * This is the owner side so we have to add the backlink here
     */
    public function addOption(Option $option) {
        $this->options->add($option);
        $option->addVariant($this);
        $this->getDescription();
    }

    public function addOptions(ArrayCollection $options) {
        foreach ($options as $option) {
            $this->addOption($option);
        }
    }

    public function removeOption(Option $option)
    {
        $this->options->removeElement($option);
        $option->removeVariant($this);
        $this->getDescription();
    }

    public function getDescription() {
        /** @var Option $option */
        $d = [];
        foreach($this->getOptions() as $option) {
            $group = $option->getIcGroup();
            $d[] = $group->getName() . ': ' . $option->getName();
        }
        sort($d);
        $this->description = implode(', ', $d);
        return $this->description;
    }

    public function setDescription(/** @noinspection PhpUnusedParameterInspection */ string $_) {
        $this->getDescription();
    }

    /**
     * @return Article $article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @return string $code
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return null|string $ean
     */
    public function getEan() : ?string
    {
        return $this->ean;
    }

    /**
     * @return float
     */
    public function getPurchasePrice()
    {
        return $this->purchasePrice;
    }

    /**
     * @return float
     */
    public function getRetailPrice()
    {
        return $this->retailPrice;
    }

    /**
     * @return bool $active
     */
    public function isActive() : bool
    {
        return $this->active;
    }

    /**
     * @return bool $active
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param null|Article $article
     */
    public function setArticle(?Article $article)
    {
        $this->article = $article;
    }

    /**
     * @param Collection $options
     */
    public function setOptions(Collection $options) {
        $this->options = $options;
        foreach ($options as $option) {
            $option->addVariant($this);
        }
        $this->getDescription();
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @param null|string $ean
     */
    public function setEan(?string $ean)
    {
        $this->ean = $ean;
    }

    /**
     * @param float $purchasePrice
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * @param float $retailPrice
     */
    public function setRetailPrice($retailPrice)
    {
        $this->retailPrice = $retailPrice;
    }

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * @return bool
     */
    public function getAccepted(): bool
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

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return array
     */
    public function getShopwareOptions(): array
    {
        return $this->shopwareOptions;
    }

    /**
     * @param array $shopwareOptions
     */
    public function setShopwareOptions(array $shopwareOptions)
    {
        $this->shopwareOptions = $shopwareOptions;
    }

    public function addShopwareOption(ShopwareOption $option) {
        $this->shopwareOptions[] = $option;
    }

    /**
     * @return Collection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Collection $images
     */
    public function setImages(Collection $images)
    {
        $this->images = $images;
        foreach ($images as $image) {
            $image->addVariant($this);
        }
    }

    /**
     * @param Image $image
     */
    public function addImage(Image $image) {
        $this->images->add($image);
        $image->addVariant($this);
    }

    public function addImages(ArrayCollection $images)
    {
        foreach($images as $image) {
            $this->addImage($image);
        }
    }

    public function removeImage(Image $image)
    {
        $this->images->removeElement($image);
        $image->removeVariant($this);
    }

    /**
     * @return string
     */
    public function getIcNumber(): string
    {
        return $this->icNumber;
    }

    /**
     * @param string $icNumber
     */
    public function setIcNumber(string $icNumber): void
    {
        $this->icNumber = $icNumber;
    }

    public function removeChildAssociations()
    {
        foreach ($this->options as $option) {
            $option->removeVariant($this);
        }
        $this->options->clear();

        foreach ($this->images as $image) {
            $image->removeVariant($this);
        }
        $this->images->clear();
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->new;
    }

    /**
     * @param bool $new
     */
    public function setNew(bool $new): void
    {
        $this->new = $new;
    }

    public function getDetail() : ?Detail
    {
        if ($this->detail === null) {
            $this->detail = Shopware()->Models()->getRepository(Variant::class)->getShopwareDetail($this);
        }
        return $this->detail;
    }

    /**
     * @param Detail $detail
     */
    public function setDetail(?Detail $detail): void
    {
        $this->detail = $detail;
    }

}
