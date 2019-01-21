<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_model_import")
 */
class Variant extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var string $category
     * @ORM\Column(type="string", nullable=false)
     */
    private $category;

    /**
     * @var string $master
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="models")
     */
    private $master;

    /**
     * @var string $model
     * @ORM\Column(type="string", nullable=false)
     */
    private $model;

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
     * @var float $purchasePrice
     * @ORM\Column(name="purchase_price", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $purchasePrice;

    /**
     * @var float $purchasePrice
     * @ORM\Column(name="retail_price", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $retailPrice;

    /**
     * @var string $image;
     * @ORM\OneToOne(targetEntity="Image")
     */
    private $image;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="Image", mappedBy="models")
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
     * @ORM\ManyToMany(targetEntity="Option", mappedBy="models")
     */
    private $options;

    /**
     * Variant constructor.
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
     * @param string $master
     */
    public function setMaster(string $master)
    {
        $this->master = $master;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param string $model
     */
    public function setModel(string $model)
    {
        $this->model = $model;
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
     * @return float
     */
    public function getPurchasePrice(): float
    {
        return $this->purchasePrice;
    }

    /**
     * @param float $purchasePrice
     */
    public function setPurchasePrice(float $purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * @return float
     */
    public function getRetailPrice(): float
    {
        return $this->retailPrice;
    }

    /**
     * @param float $retailPrice
     */
    public function setRetailPrice(float $retailPrice)
    {
        $this->retailPrice = $retailPrice;
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

    /**
     * @return ArrayCollection
     */
    public function getAdditionalImages(): ArrayCollection
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

    public function addAdditionalImage(Image $image) {
        $this->additionalImages->add($image);
        $image->addModel($this);
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
    public function getManualUrl(): string
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
     * @return ArrayCollection
     */
    public function getOptions(): ArrayCollection
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

    public function addOption(Option $option) {
        $this->options->add($option);
        $option->addModel($this);
    }
}
