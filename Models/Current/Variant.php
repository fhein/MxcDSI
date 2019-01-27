<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Current;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Article\Configurator\Option as ShopwareOption;

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
     * @var Article $article
     *
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="variants")
     */
    private $article;

    /**
     * @var string $number
     * @ORM\Column(name="number", type="string", nullable=false)
     */
    private $number;

    /**
     * @var string $ean
     *
     * @ORM\Column(name="ean", type="string", nullable=false)
     */
    private $ean;

    /**
     * @var float $purchasePrice
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
     * @var string $options
     * @ORM\Column(name="options", type="string", nullable=true)
     */
    private $options;

    /**
     * @ORM\Column(tpye="string", nullable=true)
     */
    private $images;

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

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
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
    public function getNumber(): string
    {
        return $this->number;
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
     * @param string $options
     */
    public function setOptions(string $options) {
        $this->options = $options;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
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
     * @return string
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param string $images
     */
    public function setImages($images)
    {
        $this->images = $images;
    }

}
