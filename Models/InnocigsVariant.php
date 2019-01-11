<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Article\Configurator\Option;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dropship_innocigs_variant")
 */
class InnocigsVariant extends ModelEntity
{
    /**
     * Primary Key - autoincrement value
     *
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var InnocigsArticle $article
     *
     * @ORM\ManyToOne(targetEntity="InnocigsArticle", inversedBy="variants")
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
     * @var float $priceNet
     *
     * @ORM\Column(name="price_net", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $priceNet;

    /**
     * @var float $priceRecommended
     *
     * @ORM\Column(name="price_rcmd", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $priceRecommended;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\ManyToMany(targetEntity="InnocigsOption", mappedBy="variants")
     */
    private $options;

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
     * @var int $detailId
     * @ORM\Column(type="integer", nullable=true)
     */
    private $detailId = null;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created = null;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated = null;

    /**
     * @var array $shoppwwareOptions
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
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps() {
        $now = new DateTime();
        $this->updated = $now;
        if ( null === $this->created) {
            $this->created = $now;
        }
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getOptions()//: ArrayCollection
    {
        return $this->options;
    }

    /**
     * @param InnocigsOption $option
     *
     * This is the owner side so we have to add the backlink here
     */
    public function addOption(InnocigsOption $option) {
        $this->options->add($option);
        $option->addVariant($this);
        $this->getDescription();
    }

    public function getDescription() {
        /**
         * @var InnocigsOption $option
         */
        $d = [];
        foreach($this->getOptions() as $option) {
            $group = $option->getInnocigsGroup();
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
     * @return \DateTime $created
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return \DateTime $updated
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return InnocigsArticle $article
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
     * @return float $priceNet
     */
    public function getPriceNet()
    {
        return $this->priceNet;
    }

    /**
     * @return float $priceRecommended
     */
    public function getPriceRecommended()
    {
        return $this->priceRecommended;
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
     * @param InnocigsArticle $article
     */
    public function setArticle(InnocigsArticle $article)
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
     * @param float $priceNet
     */
    public function setPriceNet($priceNet)
    {
        $this->priceNet = $priceNet;
    }

    /**
     * @param float $priceRecommended
     */
    public function setPriceRecommended($priceRecommended)
    {
        $this->priceRecommended = $priceRecommended;
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
    public function setAccepted(bool $accepted): void
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
     * @return int
     */
    public function getDetailId(): int
    {
        return $this->detailId;
    }

    /**
     * @param int $detailId
     */
    public function setDetailId(int $detailId): void
    {
        $this->detailId = $detailId;
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
    public function setShopwareOptions(array $shopwareOptions): void
    {
        $this->shopwareOptions = $shopwareOptions;
    }

    public function addShopwareOption(Option $option) {
        $this->shopwareOptions[] = $option;
    }
}
