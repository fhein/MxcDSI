<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Article\Article;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dropship_innocigs_article")
 */
class InnocigsArticle extends ModelEntity implements ValidationModelInterface {
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
     * @var string $name
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;
    /**
     * @var string $code
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $code;
    /**
     * @var string $supplier
     *
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
     * @var string $description
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $description;
    /**
     * @var string $imageUrl;
     *
     * @ORM\Column(name="image", type="string", nullable=true)
     */
    private $imageUrl;

    /**
     * @var string $manualUrl;
     *
     * @ORM\Column(name="manual", type="string", nullable=true)
     */
    private $manualUrl;
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="InnocigsVariant",
     *      mappedBy="article",
     *      cascade={"persist", "remove"}
     * )
     */
    private $variants;
    /**
     * @var Article
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Article")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id", nullable=true)
     */
    private $article;
    /**
     * @var boolean $active
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $active = false;

    /**
     * @var boolean $accepted
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $accepted = true;

    /**
     * @var DateTime $created
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created = null;

    /**
     * @var int $configSetId
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $configSetId = null;

    /**
     * @var DateTime $updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated = null;

    /**
     * InnocigsArticle constructor.
     */
    public function __construct() {
        $this->variants = new ArrayCollection();
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function getVariants()
    {
        return $this->variants;
    }
    /**
     * @param InnocigsVariant $variant
     */
    public function addVariant(InnocigsVariant $variant) {
        $this->variants->add($variant);
        $variant->setArticle($this);
    }
    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
    /**
     * @param string $code
     */
    public function setCode(string $code)
    {
        $this->code = $code;
    }
    /**
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
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
     * @return int
     */
    public function getConfigSetId(): int
    {
        return $this->configSetId;
    }
    /**
     * @param int $configSetId
     */
    public function setConfigSetId(int $configSetId)
    {
        $this->configSetId = $configSetId;
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
    public function setSupplier(string $supplier)
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
    public function setBrand(string $brand)
    {
        $this->brand = $brand;
    }
    /**
     * @return Article
     */
    public function getArticle()
    {
        return $this->article;
    }
    /**
     * @param Article $article
     */
    public function setArticle(Article $article)
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
    public function setManualUrl(string $manualUrl)
    {
        $this->manualUrl = $manualUrl;
    }
}