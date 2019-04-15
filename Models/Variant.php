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
     * @var string $number
     * @ORM\Column(name="ic_number", type="string", nullable=false)
     */
    private $icNumber;

    /**
     * @var string $number
     * @ORM\Column(name="number", type="string", nullable=true)
     */
    private $number;

    /**
     * @var Product $product
     *
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="variants")
     */
    private $product;

    /**
     * @var Detail
     */
    private $detail;

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
     * @var float $recommendedRetailPrice
     *
     * @ORM\Column(name="uvp", type="string", nullable=false)
     */
    private $recommendedRetailPrice;

    /**
     * @var string $retailPrices
     *
     * @ORM\Column(name="retail_prices", type="string", nullable=true)
     */
    private $retailPrices;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Option", inversedBy="variants", cascade="persist")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_variants_options")
     */
    private $options;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Image", inversedBy="variants", cascade="persist")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_variants_images")
     */
    private $images;

    /**
     * @var @ORM\Column(type="string", nullable=true)
     */
    private $description;

    /**
     * Pieces per order derived from the variants's package size option.
     *
     * @var int $piecesPerOrder
     */
    private $piecesPerOrder;

    /**
     * @var boolean $active
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $active = false;

    /**
     * @var boolean $accepted
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $accepted = true;

    /**
     * @var boolean $new
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $new = true;

    /** @var boolean $valid */
    private $valid;

    /**
     * @var array $shopwareOptions
     *
     * This property will not be persisted. The array gets filled by
     * ConfiguratorOptionMapper, which creates Shopware options from our
     * options and adds the created shopware options here.
     *
     * Later on, the ProductMapper will create the shopware detail
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
     * @param Option $option ./in
     *
     * This is the owner side so we have to add the backlink here
     */
    public function addOption(Option $option)
    {
        $this->options->add($option);
        $option->addVariant($this);
        $this->getDescription();
    }

    public function addOptions(ArrayCollection $options)
    {
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

    public function getDescription()
    {
        /** @var Option $option */
        $d = [];
        foreach ($this->getOptions() as $option) {
            $group = $option->getIcGroup();
            $d[] = $group->getName() . ': ' . $option->getName();
        }
        sort($d);
        $this->description = implode(', ', $d);
        return $this->description;
    }

    public function setDescription(
        /** @noinspection PhpUnusedParameterInspection */
        string $_
    ) {
        $this->getDescription();
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @return null|string $ean
     */
    public function getEan(): ?string
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
    public function getRecommendedRetailPrice()
    {
        return $this->recommendedRetailPrice;
    }

    /**
     * @return bool $active
     */
    public function isActive(): bool
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
     * @param null|Product $product
     */
    public function setProduct(?Product $product)
    {
        $this->product = $product;
    }

    /**
     * @param Collection $options
     */
    public function setOptions(Collection $options)
    {
        $this->options = $options;
        foreach ($options as $option) {
            $option->addVariant($this);
        }
        $this->getDescription();
    }

    /**
     * @param string|null $number
     */
    public function setNumber(?string $number)
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
     * @param float $recommendedRetailPrice
     */
    public function setRecommendedRetailPrice($recommendedRetailPrice)
    {
        $this->recommendedRetailPrice = $recommendedRetailPrice;
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

    public function addShopwareOption(ShopwareOption $option)
    {
        if (!in_array($option, $this->shopwareOptions, true)) {
            $this->shopwareOptions[] = $option;
        }
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
    public function addImage(Image $image)
    {
        $this->images->add($image);
        $image->addVariant($this);
    }

    public function addImages(ArrayCollection $images)
    {
        foreach ($images as $image) {
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
    public function setIcNumber(string $icNumber)
    {
        $this->icNumber = $icNumber;
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
    public function setNew(bool $new)
    {
        $this->new = $new;
    }

    public function getDetail(): ?Detail
    {
        if ($this->detail === null) {
            $this->detail = Shopware()->Models()->getRepository(Variant::class)->getDetail($this);
        }
        return $this->detail;
    }

    /**
     * @param Detail $detail
     */
    public function setDetail(?Detail $detail)
    {
        $this->detail = $detail;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if ($this->valid === null) {
            $this->valid = Shopware()->Models()->getRepository(Variant::class)->validateVariant($this);
        }
        return $this->valid;
    }

    /**
     * @return int
     */
    public function getPiecesPerOrder()
    {
        if (! $this->piecesPerOrder) {
            $this->piecesPerOrder = Shopware()->Models()->getRepository(Variant::class)->getPiecesPerOrder($this);
        }
        return $this->piecesPerOrder;
    }

    /**
     * @param int $piecesPerOrder
     */
    public function setPiecesPerOrder($piecesPerOrder)
    {
        $this->piecesPerOrder = $piecesPerOrder;
    }

    /**
     * @return string
     */
    public function getRetailPrices(): string
    {
        return $this->retailPrices;
    }

    /**
     * @param string $retailPrices
     */
    public function setRetailPrices(string $retailPrices): void
    {
        $this->retailPrices = $retailPrices;
    }
}
