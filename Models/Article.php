<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
     * @ORM\ManyToMany(targetEntity="Article")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_articles_spareparts",
     *     joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="sparepart_id", referencedColumnName="id")}
     *     )
     */
    private $spareParts;

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
     * @var string $commonName
     *
     * @ORM\Column(name="common_name", type="string", nullable=true)
     */
    private $commonName;

    /**
     * @var string $type
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $type;

    /**
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
     * @var int $dosageMin
     *
     * @ORM\Column(name="rcmd_cnctr_min", type="integer", nullable=true)
     */
    private $dosageMin;

    /**
     * @var int $dosageMax
     *
     * @ORM\Column(name="rcmd_cnctr_max", type="integer", nullable=true)
     */
    private $dosageMax;

    /**
     * @var int $pg
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pg;

    /**
     * @var int $vg
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $vg;

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
     * @var boolean $new
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $new = true;

    /**
     * @var string $flavor
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $flavor;

    const TYPE_E_CIGARETTE      = 0;
    const TYPE_BOX_MOD          = 1;
    const TYPE_E_PIPE           = 2;
    const TYPE_CLEAROMIZER      = 3;
    const TYPE_LIQUID           = 4;
    const TYPE_AROMA            = 5;
    const TYPE_SHAKE_VAPE       = 6;
    const TYPE_HEAD             = 7;
    const TYPE_TANK             = 8;
    const TYPE_SEAL             = 9;
    const TYPE_DRIP_TIP         = 10;
    const TYPE_POD              = 11;
    const TYPE_CARTRIDGE        = 12;
    const TYPE_CELL             = 13;
    const TYPE_CELL_BOX         = 14;
    const TYPE_BASE             = 15;
    const TYPE_CHARGER          = 16;
    const TYPE_BAG              = 17;
    const TYPE_TOOL             = 18;
    const TYPE_WADDING          = 19; // Watte
    const TYPE_WIRE             = 20;
    const TYPE_BOTTLE           = 21;
    const TYPE_SQUONKER_BOTTLE  = 22;
    const TYPE_VAPORIZER        = 23;
    const TYPE_SHOT             = 24;
    const TYPE_CABLE            = 25;
    const TYPE_BOX_MOD_CELL     = 26;
    const TYPE_COIL             = 27;
    const TYPE_RDA_BASE         = 28;
    const TYPE_MAGNET           = 29;
    const TYPE_MAGNET_ADAPTER   = 30;
    const TYPE_ACCESSORY        = 31;
    const TYPE_BATTERY_CAP      = 32;
    const TYPE_UNKNOWN          = 33;

    protected $types = [
        self::TYPE_E_CIGARETTE      => 'E_CIGARETTE',
        self::TYPE_BOX_MOD          => 'BOX_MOD',
        self::TYPE_E_PIPE           => 'E_PIPE',
        self::TYPE_CLEAROMIZER      => 'CLEAROMIZER',
        self::TYPE_LIQUID           => 'LIQUID',
        self::TYPE_AROMA            => 'AROMA',
        self::TYPE_SHAKE_VAPE       => 'SHAKE_VAPE',
        self::TYPE_HEAD             => 'HEAD',
        self::TYPE_TANK             => 'TANK',
        self::TYPE_SEAL             => 'SEAL',
        self::TYPE_DRIP_TIP         => 'DRIP_TIP',
        self::TYPE_POD              => 'POD',
        self::TYPE_CARTRIDGE        => 'CARTRIDGE',
        self::TYPE_CELL             => 'CELL',
        self::TYPE_CELL_BOX         => 'CELL_BOX',
        self::TYPE_BASE             => 'BASE',
        self::TYPE_CHARGER          => 'CHARGER',
        self::TYPE_BAG              => 'BAG',
        self::TYPE_TOOL             => 'TOOL',
        self::TYPE_WADDING          => 'WADDING', // Watte
        self::TYPE_WIRE             => 'WIRE',
        self::TYPE_BOTTLE           => 'BOTTLE',
        self::TYPE_SQUONKER_BOTTLE  => 'SQUONKER_BOTTLE',
        self::TYPE_VAPORIZER        => 'VAPORIZER',
        self::TYPE_SHOT             => 'SHOT',
        self::TYPE_CABLE            => 'CABLE',
        self::TYPE_BOX_MOD_CELL     => 'BOX_MOD_CELL',
        self::TYPE_COIL             => 'COIL',
        self::TYPE_RDA_BASE         => 'RDA_BASE',
        self::TYPE_MAGNET           => 'MAGNET',
        self::TYPE_MAGNET_ADAPTER   => 'MAGNET_ADAPTER',
        self::TYPE_ACCESSORY        => 'ACCESSORY',
        self::TYPE_BATTERY_CAP      => 'BATTERY_CAP',
        self::TYPE_UNKNOWN          => 'UNKNOWN',
    ];

    /**
     * Article constructor.
     */
    public function __construct() {
        $this->variants = new ArrayCollection();
        $this->spareParts = new ArrayCollection();
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
        $variant->setArticle(null);
    }

    public function setVariants($variants) {
        $this->setOneToMany($variants, 'MxcDropshipInnocigs\Models\Variant', 'variants');
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
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    /**
     * @return null|string
     */
    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    /**
     * @param null|string $supplier
     */
    public function setSupplier(?string $supplier)
    {
        $this->supplier = $supplier;
    }

    /**
     * @return null|string
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param null|string $brand
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

    /**
     * @return string
     */
    public function getFlavor(): string
    {
        return $this->flavor;
    }

    /**
     * @param string $flavor
     */
    public function setFlavor(string $flavor): void
    {
        $this->flavor = $flavor;
    }

    public function getSpareParts()
    {
        return $this->spareParts;
    }

    public function addSparePart(Article $sparePart)
    {
        $this->spareParts->add($sparePart);
    }

    public function setSpareParts(Collection $spareParts)
    {
        $this->spareParts = $spareParts;
    }

    public function removeSparePart(Article $sparePart)
    {
        $this->spareParts->removeElement($sparePart);
    }

    /**
     * @return string
     */
    public function getCommonName(): ?string
    {
        return $this->commonName;
    }

    /**
     * @param string $commonName
     */
    public function setCommonName(?string $commonName): void
    {
        $this->commonName = $commonName;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param $type
     */
    public function setType($type): void
    {
        if (is_int($type)) $type = $this->types[$type];
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
    public function setPiecesPerPack(?int $piecesPerPack): void
    {
        $this->piecesPerPack = $piecesPerPack;
    }

    /**
     * @return int|null
     */
    public function getDosageMin(): ?int
    {
        return $this->dosageMin;
    }

    /**
     * @param int|null $dosageMin
     */
    public function setDosageMin(?int $dosageMin): void
    {
        $this->dosageMin = $dosageMin;
    }

    /**
     * @return int|null
     */
    public function getDosageMax(): ?int
    {
        return $this->dosageMax;
    }

    public function setDosage(?array $p)
    {
        $p = $p ?? [ 'min' => null, 'max' => null ];
        $this->setDosageMax($p['max']);
        $this->setDosageMin($p['min']);
    }

    public function getDosage()
    {
        return [
            'min' => $this->getDosageMin(),
            'max' => $this->getDosageMax(),
        ];
    }

    /**
     * @param int|null $dosageMax
     */
    public function setDosageMax(?int $dosageMax): void
    {
        $this->dosageMax = $dosageMax;
    }

    /**
     * @return int|null
     */
    public function getPg(): ?int
    {
        return $this->pg;
    }

    /**
     * @param int|null $pg
     */
    public function setPg(?int $pg): void
    {
        $this->pg = $pg;
    }

    /**
     * @return int|null
     */
    public function getVg(): ?int
    {
        return $this->vg;
    }

    /**
     * @param int|null $vg
     */
    public function setVg(?int $vg): void
    {
        $this->vg = $vg;
    }
}