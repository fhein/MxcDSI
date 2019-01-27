<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Current;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Article\Article as ShopwareArticle;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_article")
 * @ORM\Entity(repositoryClass="ArticleRepository")
 */
class Article extends ModelEntity  {

    use BaseModelTrait;

    /**
     * @var string $number
     * @ORM\Column(type="string", nullable=false)
     */
    private $number;

    /**
     * @var string $code
     * @ORM\Column(name="ic_number", type="string", nullable=false)
     */
    private $icNumber;

    /**
     * @var string $name
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * @var string $category
     * @ORM\Column(type="string")
     */
    private $category;

    /**
     * @var string $description
     * @ORM\Column(type="string", nullable=true)
     */
    private $description;

    /**
     * @var string $imageUrl;
     * @ORM\Column(type="string", nullable=true)
     */
    private $imageUrl;

    /**
     * @var string $manufacturer;
     * @ORM\Column(name="manufacturer", type="string", nullable=true)
     */
    private $manufacturer;

    /**
     * @var string $manualUrl;
     * @ORM\Column(name="manual", type="string", nullable=true)
     */
    private $manualUrl;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="Variant",
     *      mappedBy="article",
     *      cascade={"persist", "remove"}
     * )
     */
    private $variants;

    /**
     * @var string $supplier
     * @ORM\Column(type="string", nullable=true)
     */
    private $supplier;

    /**
     * @var string $brand
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $brand;
    /**
     * @var ShopwareArticle
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Article")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=true)
     */
    private $article;

    /**
     * @var boolean $active
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $active = false;

    /**
     * @var boolean $accepted
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $accepted = true;

    /**
     * ImportArticle constructor.
     */
    public function __construct() {
        $this->variants = new ArrayCollection();
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
     * @return boolean
     */
    public function isActive() : bool {
        return $this->active;
    }
    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }
    /**
     * @param bool $active
     */
    public function setActive(bool $active)
    {
        $this->active = $active;
    }
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * @param Variant $variant
     */
    public function addVariant(Variant $variant) {
        $this->variants->add($variant);
        $variant->setArticle($this);
    }


    public function removeVariant(Variant $variant) {
        $this->variants->removeElement($variant);

    }

    public function setVariants($variants) {
        $this->setOneToMany($variants, 'MxcDropshipInnocigs\Models\Current\Variant', 'variants');
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
     * @param bool $accepted
     */
    public function setAccepted(bool $accepted)
    {
        $this->accepted = $accepted;
    }
    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted;
    }
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }
    /**
     * @return string
     */
    public function getImageUrl(): string
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
    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    /**
     * @param string $supplier
     */
    public function setSupplier(?string $supplier)
    {
        $this->supplier = $supplier;
    }

    /**
     * @return string
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand(?string $brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return ShopwareArticle
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param ShopwareArticle $article
     */
    public function setArticle(ShopwareArticle $article)
    {
        $this->article = $article;
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
    public function setManualUrl(?string $manualUrl)
    {
        $this->manualUrl = $manualUrl;
    }

    /**
     * @return string
     */
    public function getCategory()
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
    public function getManufacturer()
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
}