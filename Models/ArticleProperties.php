<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_article_properties")
 * @ORM\Entity(repositoryClass="ArticlePropertiesRepository")
 */
class ArticleProperties extends ModelEntity
{
    use BaseModelTrait;

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
     * @var string $code
     * @ORM\Column(name="ic_number", type="string", nullable=false)
     */
    private $icNumber;

    /**
     * @var string $brand
     * @ORM\Column(type="string", nullable=true)
     */
    private $brand;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $supplier;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $associatedProduct;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $type;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $content;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fillup;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $flavor;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $capacity;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $power;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $piecesPerPack;

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
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(?string $name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(?int $type)
    {
        $this->type = $this->types[$type];
    }

    /**
     * @return int
     */
    public function getContent(): ?int
    {
        return $this->content;
    }

    /**
     * @param int $content
     */
    public function setContent(?int $content)
    {
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getFillup(): ?int
    {
        return $this->fillup;
    }

    /**
     * @param int $fillup
     */
    public function setFillup(?int $fillup)
    {
        $this->fillup = $fillup;
    }

    /**
     * @return string
     */
    public function getFlavor(): ?string
    {
        return $this->flavor;
    }

    /**
     * @param string $flavor
     */
    public function setFlavor(?string $flavor)
    {
        $this->flavor = $flavor;
    }

    /**
     * @return int
     */
    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    /**
     * @param int $capacity
     */
    public function setCapacity(?int $capacity)
    {
        $this->capacity = $capacity;
    }

    /**
     * @return int
     */
    public function getPower(): ?int
    {
        return $this->power;
    }

    /**
     * @param int $power
     */
    public function setPower(?int $power)
    {
        $this->power = $power;
    }

    /**
     * @return int
     */
    public function getPiecesPerPack(): ?int
    {
        return $this->piecesPerPack;
    }

    /**
     * @param int $piecesPerPack
     */
    public function setPiecesPerPack(?int $piecesPerPack)
    {
        $this->piecesPerPack = $piecesPerPack;
    }

    /**
     * @return string
     */
    public function getAssociatedProduct(): ?string
    {
        return $this->associatedProduct;
    }

    /**
     * @param string $associatedProduct
     */
    public function setAssociatedProduct(?string $associatedProduct)
    {
        $this->associatedProduct = $associatedProduct;
    }
}