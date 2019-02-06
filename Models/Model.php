<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_model")
 * @ORM\Entity(repositoryClass="ModelRepository")
 */
class Model extends ModelEntity
{
    use BaseModelTrait;

    /**
     * @var string $category
     * @ORM\Column(type="string", nullable=true)
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
     * @ORM\Column(type="string", nullable=true)
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
     * @var string $images;
     * @ORM\Column(name="image_url", type="text", nullable=true)
     */
    private $images;

    /**
     * @var string $manufacturer
     * @ORM\Column(type="string", nullable=true)
     */
    private $manufacturer;

    /**
     * @var string $manual ;
     * @ORM\Column(name="manual", type="string", nullable=true)
     */
    private $manual;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $options;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $deleted = false;

    public function fromImport(array $data) {
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            $this->$setter($value);
        }
    }

    /**
     * @return null|string
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param null|string $category
     */
    public function setCategory(?string $category)
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
     * @return null|string
     */
    public function getEan(): ?string
    {
        return $this->ean;
    }

    /**
     * @param null|string $ean
     */
    public function setEan(?string $ean)
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
     * @return null|string
     */
    public function getImages() : ?string
    {
        return $this->images;
    }

    /**
     * @param null|string $images
     */
    public function setImages(?string $images)
    {
        $this->images = $images;
    }

    /**
     * @return null|string
     */
    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    /**
     * @param null|string $manufacturer
     */
    public function setManufacturer(?string $manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * @return null|string
     */
    public function getManual(): ?string
    {
        return $this->manual;
    }

    /**
     * @param null|string $manual
     */
    public function setManual(?string $manual)
    {
        $this->manual = $manual;
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

    /**
     * @return bool
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}
