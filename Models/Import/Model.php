<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Import;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_model_import")
 * @ORM\Entity(repositoryClass="ModelRepository")
 */
class Model extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var string $category
     * @ORM\Column(type="string", nullable=false)
     */
    private $category;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $master;

    /**
     * @var string
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
     * @var string $purchasePrice
     * @ORM\Column(name="purchase_price", type="string", nullable=false)
     */
    private $purchasePrice;

    /**
     * @var string $purchasePrice
     * @ORM\Column(name="retail_price", type="string", nullable=false)
     */
    private $retailPrice;

    /**
     * @var string $imageUrl;
     * @ORM\Column(name="image_url", type="string", nullable=true)
     */
    private $imageUrl;

    /**
     * @var string
     * @ORM\Column(name="addl_images", type="text", nullable=true)
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
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $options;

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
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     */
    public function setImageUrl(string $imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return string
     */
    public function getAdditionalImages(): string
    {
        return $this->additionalImages;
    }

    /**
     * @param null|string $additionalImages
     */
    public function setAdditionalImages(?string $additionalImages)
    {
        $this->additionalImages = $additionalImages;
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
     * @param null|string $manualUrl
     */
    public function setManualUrl(?string $manualUrl)
    {
        $this->manualUrl = $manualUrl;
    }

    /**
     * @return string
     */
    public function getOptions(): string
    {
        return $this->options;
    }

    /**
     * @param string $options
     */
    public function setOptions(string $options)
    {
        $this->options = $options;
    }
}
