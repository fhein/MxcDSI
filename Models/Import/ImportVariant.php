<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_model_import")
 * @ORM\Entity(repositoryClass="ImportVariantRepository")
 */
class ImportVariant extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var string $category
     * @ORM\Column(type="string", nullable=false)
     */
    private $category;

    /**
     * @var ImportArticle $master
     * @ORM\ManyToOne(targetEntity="ImportArticle", inversedBy="variants")
     */
    private $master;

    /**
     * @var string $number
     * @ORM\Column(type="string", nullable=false)
     */
    private $number;

    /**
     * @var string $ean
     * @ORM\Column(type="string")
     */
    private $ean;

    /**
     * @var string $name
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * @var string $purchasePrice
     * @ORM\Column(name="purchase_price", type="string", nullable = false)
     */
    private $purchasePrice;

    /**
     * @var string $purchasePrice
     * @ORM\Column(name="retail_price", type="string", nullable=false)
     */
    private $retailPrice;

    /**
     * @var ImportImage $image;
     * @ORM\OneToOne(targetEntity="ImportImage", cascade="persist")
     */
    private $image;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="ImportImage", inversedBy="variants", cascade="persist")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_import_variants_images")
     */
    private $additionalImages;

    /**
     * @var string $manufacturer ;
     * @ORM\Column(name="manufacturer", type="string", nullable=true)
     */
    private $manufacturer;

    /**
     * @var string $manualUrl ;
     * @ORM\Column(name="manual", type="string", nullable=true)
     */
    private $manualUrl;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="ImportOption", inversedBy="variants")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_import_variants_options")
     */
    private $options;

    /**
     * ImportVariant constructor.
     */
    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->additionalImages = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getMaster(): string
    {
        return $this->master;
    }

    /**
     * @param ImportArticle $master
     */
    public function setMaster(ImportArticle $master)
    {
        $this->master = $master;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber(string $number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getEan(): string
    {
        return $this->ean;
    }

    /**
     * @param string $ean
     */
    public function setEan(string $ean)
    {
        $this->ean = $ean;
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
     * @return string
     */
    public function getPurchasePrice(): string
    {
        return $this->purchasePrice;
    }

    /**
     * @param string $purchasePrice
     */
    public function setPurchasePrice(string $purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * @return string
     */
    public function getRetailPrice(): string
    {
        return $this->retailPrice;
    }

    /**
     * @param string $retailPrice
     */
    public function setRetailPrice(string $retailPrice)
    {
        $this->retailPrice = $retailPrice;
    }

    /**
     * @return ImportImage
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param ImportImage $image
     */
    public function setImage(ImportImage $image)
    {
        $this->image = $image;
    }

    /**
     * @return Collection
     */
    public function getAdditionalImages(): Collection
    {
        return $this->additionalImages;
    }

    /**
     * @param ArrayCollection $additionalImages
     */
    public function setAdditionalImages(ArrayCollection $additionalImages)
    {
        $this->additionalImages = $additionalImages;
    }

    public function addAdditionalImage(ImportImage $image) {
        $this->additionalImages->add($image);
        $image->addVariant($this);
    }

    /**
     * @return string
     */
    public function getManufacturer(): string
    {
        return $this->manufacturer;
    }

    /**
     * @param string $manufacturer
     */
    public function setManufacturer(string $manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * @return string
     */
    public function getManualUrl(): ?string
    {
        return $this->manualUrl;
    }

    /**
     * @param string $manualUrl
     */
    public function setManualUrl(string $manualUrl)
    {
        $this->manualUrl = $manualUrl;
    }

    /**
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @param ArrayCollection $options
     */
    public function setOptions(ArrayCollection $options)
    {
        $this->options = $options;
    }

    public function addOption(ImportOption $option) {
        $this->options->add($option);
        $option->addModel($this);
    }
}
