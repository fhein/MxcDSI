<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_product")
 * @ORM\Entity(repositoryClass="ProductRepository")
 */
class Product extends ModelEntity  {

    use BaseModelTrait;

    /**
     * @var string $icNumber
     * @ORM\Column(name="ic_number", type="string", nullable=false)
     */
    private $icNumber;

    /**
     * PropertyMapper mapped
     *
     * @var string $number
     * @ORM\Column(type="string", nullable=true)
     */
    private $number;

    /**
     * @ORM\ManyToMany(targetEntity="Product")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_products_related",
     *     joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="related_id", referencedColumnName="id")}
     *     )
     */
    private $relatedProducts;

    /**
     * @ORM\ManyToMany(targetEntity="Product")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_products_similar",
     *     joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="similar_id", referencedColumnName="id")}
     *     )
     */
    private $similarProducts;

    /**
     * PropertyMapper mapped
     *
     * @var string $name
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * PropertyMapper mapped
     *
     * @var string $commonName
     *
     * @ORM\Column(name="common_name", type="string", nullable=true)
     */
    private $commonName;

    /**
     * PropertyMapper mapped
     *
     * @var string $type
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $type;

    /**
     * PropertyMapper mapped
     *
     * This property reflects the pieces per pack information extracted
     * from the product name (example: (5 Stück pro Packung)).
     *
     * @var int $piecesPerPack
     * @ORM\Column(type="integer", nullable=true)
     */
    private $piecesPerPack;

    /**
     * @var string $category
     * @ORM\Column(type="string", nullable=true)
     */
    private $category;

    /**
     * @var string $description
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string $manufacturer;
     * @ORM\Column(name="manufacturer", type="string", nullable=true)
     */
    private $manufacturer;

    /**
     * @var string $manual;
     * @ORM\Column(name="manual", type="string", nullable=true)
     */
    private $manual;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Variant", mappedBy="product", cascade={"persist", "remove"})
     */
    private $variants;

    private $validVariants;

    /**
     * PropertyMapper mapped
     *
     * @var string $supplier
     * @ORM\Column(type="string", nullable=true)
     */
    private $supplier;

    /**
     * PropertyMapper mapped
     *
     * @var string $brand
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $brand;

    /**
     * @var float $tax
     *
     * @ORM\Column(type="float", nullable=false)
     */
    private $tax = 19.0;


    private $article;

    /**
     * Aroma dosage, PropertyMapper mapped
     *
     * @var string $dosage
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $dosage;

    /**
     * VG/PG, PropertyMapper mapped
     *
     * @var string $base
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $base;

    /**
     * @var string
     * @ORM\Column(name="retail_dp",type="string", nullable=true)
     */
    private $retailPriceDampfplanet;

    /**
     * @var string
     * @ORM\Column(name="retail_others",type="string", nullable=true)
     */
    private $retailPriceOthers;

    /**
     * @var boolean $createRelatedProducts
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $createRelatedProducts = true;

    /**
     * @var boolean $activateCreatedRelatedProducts
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activateCreatedRelatedProducts = false;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $createSimilarProducts = false;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activateCreatedSimilarProducts = false;

    /**
     * @var boolean $accepted
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $accepted = true;

    /**
     * @var boolean $active
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $active = false;

    /**
     * @var boolean $active
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $linked = false;

    /**
     * @var boolean $new
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $new = true;

    /** @var boolean $valid */
    private $valid;

    /**
     * @var string $flavor
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $flavor;

    /**
     * Product constructor.
     */
    public function __construct() {
        $this->variants = new ArrayCollection();
        $this->relatedProducts = new ArrayCollection();
        $this->similarProducts = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
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
        $variant->setProduct($this);
    }

    public function removeVariant(Variant $variant) {
        $this->variants->removeElement($variant);
        $variant->setProduct(null);
    }

    public function setVariants($variants) {
        $this->setOneToMany($variants, 'MxcDropshipInnocigs\Models\Variant', 'variants');
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
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
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return null|string
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * @param null|string $supplier
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;
    }

    /**
     * @return null|string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param null|string $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    public function getArticle()
    {
        if ($this->article === null) {
            $this->article = Shopware()->Models()->getRepository(Product::class)->getArticle($this);
        }
        $this->linked = $this->article !== null;
        return $this->article;
    }

    public function setArticle($article)
    {
        $this->article = $article;
        $this->linked = $article !== null;
    }

    public function getValidVariants()
    {
        if (! $this->validVariants) {
            $this->validVariants = Shopware()->Models()->getRepository(Product::class)->getValidVariants($this);
        }
        return $this->validVariants;
    }

    /**
     * @return null|string
     */
    public function getManual()
    {
        return $this->manual;
    }

    /**
     * @param null|string $manual
     */
    public function setManual($manual)
    {
        $this->manual = $manual;
    }

    /**
     * @return null|string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param null|string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return null|string
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * @param null|string $manufacturer
     */
    public function setManufacturer($manufacturer)
    {
        $this->manufacturer = $manufacturer;
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

    /**
     * @return string|null
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * @param null|string $flavor
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;
    }

    public function getRelatedProducts()
    {
        return $this->relatedProducts;
    }

    public function addRelatedProduct(Product $product)
    {
        $this->relatedProducts->add($product);
    }

    public function setRelatedProducts(Collection $relatedProducts)
    {
        $this->relatedProducts = $relatedProducts ?? new ArrayCollection();
    }

    public function removeRelatedProduct(Product $product)
    {
        $this->relatedProducts->removeElement($product);
    }

    public function getSimilarProducts()
    {
        return $this->similarProducts;
    }

    public function addSimilarProduct(Product $product)
    {
        $this->similarProducts->add($product);
    }

    public function setSimilarProducts(Collection $similarProducts)
    {
        $this->similarProducts = $similarProducts ?? new ArrayCollection();
    }

    public function removeSimilarProduct(Product $product)
    {
        $this->similarProducts->removeElement($product);
    }

    /**
     * @return string
     */
    public function getCommonName()
    {
        return $this->commonName;
    }

    /**
     * @param string $commonName
     */
    public function setCommonName($commonName)
    {
        $this->commonName = $commonName;
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param null|string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int|null
     */
    public function getPiecesPerPack(): ?int
    {
        return $this->piecesPerPack;
    }

    /**
     * @param int|null $piecesPerPack
     */
    public function setPiecesPerPack($piecesPerPack)
    {
        $this->piecesPerPack = $piecesPerPack;
    }


    /**
     * @param string|null $dosage
     */
    public function setDosage($dosage)
    {
        $this->dosage = $dosage;
    }

    /**
     * @return string|null
     */
    public function getDosage()
    {
        return $this->dosage;
    }

    /**
     * @return bool
     */
    public function getActivateCreatedRelatedProducts(): bool
    {
        return $this->activateCreatedRelatedProducts;
    }

    /**
     * @param bool $activateCreatedRelatedProducts
     */
    public function setActivateCreatedRelatedProducts(bool $activateCreatedRelatedProducts)
    {
        $this->activateCreatedRelatedProducts = $activateCreatedRelatedProducts;
    }

    /**
     * @return string|null
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @param string|null $base
     */
    public function setBase($base)
    {
        $this->base = $base;
    }

    /**
     * @return bool
     */
    public function getActivateCreatedSimilarProducts(): bool
    {
        return $this->activateCreatedSimilarProducts;
    }

    /**
     * @param bool $activateCreatedSimilarProducts
     */
    public function setActivateCreatedSimilarProducts(bool $activateCreatedSimilarProducts)
    {
        $this->activateCreatedSimilarProducts = $activateCreatedSimilarProducts;
    }

    /**
     * @return float
     */
    public function getTax(): float
    {
        return $this->tax;
    }

    /**
     * @param float $tax
     */
    public function setTax(float $tax)
    {
        $this->tax = $tax;
    }

    /**
     * @return bool
     */
    public function getCreateSimilarProducts(): bool
    {
        return $this->createSimilarProducts;
    }

    /**
     * @param bool $createSimilarProducts
     */
    public function setCreateSimilarProducts(bool $createSimilarProducts)
    {
        $this->createSimilarProducts = $createSimilarProducts;
    }

    /**
     * @return bool
     */
    public function getCreateRelatedProducts(): bool
    {
        return $this->createRelatedProducts;
    }

    /**
     * @param bool $createRelatedProducts
     */
    public function setCreateRelatedProducts(bool $createRelatedProducts)
    {
        $this->createRelatedProducts = $createRelatedProducts;
    }

    public function isValid() {
        if (! $this->valid) {
            $this->valid  = Shopware()->Models()->getRepository(Product::class)->validate($this);
        }
        return $this->valid;
    }

    /**
     * @return bool
     */
    public function isLinked(): bool
    {
        $this->getArticle();
        return $this->linked;
    }

    /**
     * @param bool $linked
     */
    public function setLinked(bool $linked)
    {
        $this->linked = $linked;
    }

    /**
     * @return string
     */
    public function getRetailPriceDampfplanet()
    {
        return $this->retailPriceDampfplanet;
    }

    /**
     * @param string $retailPriceDampfplanet
     */
    public function setRetailPriceDampfPlanet($retailPriceDampfplanet)
    {
        $this->retailPriceDampfplanet = $retailPriceDampfplanet;
    }

    /**
     * @return string
     */
    public function getRetailPriceOthers()
    {
        return $this->retailPriceOthers;
    }

    /**
     * @param string $retailPriceOthers
     */
    public function setRetailPriceOthers($retailPriceOthers)
    {
        $this->retailPriceOthers = $retailPriceOthers;
    }
}