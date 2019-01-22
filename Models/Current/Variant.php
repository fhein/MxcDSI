<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Current;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Article\Configurator\Option as ShopwareOption;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_variant")
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
     * @var string $code
     *
     * @ORM\Column(name="code", type="string", nullable=false)
     */
    private $code;

    /**
     * @var string $ean
     *
     * @ORM\Column(name="ean", type="string", nullable=false)
     */
    private $ean;

    /**
     * @var float $purchasePrice
     *
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
     * @var string
     * @ORM\Column(name="description", type="string", nullable=true)
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

    public function __construct() {
        $this->options = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getOptions()//: ArrayCollection
    {
        return $this->options;
    }

    /**
     * @param Option $option
     *
     * This is the owner side so we have to add the backlink here
     */
    public function addOption(Option $option) {
        $this->options->add($option);
        $option->addVariant($this);
        $this->getDescription();
    }

    public function getDescription() {
        /** @var Option $option */
        $d = [];
        foreach($this->getOptions() as $option) {
            $group = $option->getIcGroup();
            $d[] = sprintf('%s: %s', $group->getName(), $option->getName());
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
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string $ean
     */
    public function getEan()
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
     * @param Article $article
     */
    public function setArticle(Article $article)
    {
        $this->article = $article;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @param string $ean
     */
    public function setEan($ean)
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
     * @return ArrayCollection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param array $images
     */
    public function setImages($images)
    {
        $this->setOneToMany($images, 'MxcDropshipInnocigs\Models\Current\Image', 'images');
    }

    /**
     * @param Image $image
     */
    public function addImage(Image $image) {
        $this->images->add($image);
        $image->addVariant($this);
    }
}
