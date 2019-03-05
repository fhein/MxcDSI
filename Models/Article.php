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
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_articles_related",
     *     joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="related_id", referencedColumnName="id")}
     *     )
     */
    private $relatedArticles;

    /**
     * @ORM\ManyToMany(targetEntity="Article")
     * @ORM\JoinTable(name="s_plugin_mxc_dsi_x_articles_similar",
     *     joinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="similar_id", referencedColumnName="id")}
     *     )
     */
    private $similarArticles;

    /**
     * @var string $icNumber
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

    private $article;

    /**
     * Aroma dosage
     *
     * @var string $dosage
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $dosage;

    /**
     * VG/PG
     *
     * @var string $base
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $base;

    /**
     * @var boolean $active
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $active = false;

    /**
     * @var boolean $activateRelatedArticles
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activateRelatedArticles = false;

    /**
     * @var boolean $activateSimilarArticles
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activateSimilarArticles = false;

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

    /**
     * Article constructor.
     */
    public function __construct() {
        $this->variants = new ArrayCollection();
        $this->relatedArticles = new ArrayCollection();
        $this->similarArticles = new ArrayCollection();
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

    public function getArticle()
    {
        return $this->article;
    }

    public function setArticle(?ShopwareArticle $article)
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
    public function getFlavor(): ?string
    {
        return $this->flavor;
    }

    /**
     * @param string $flavor
     */
    public function setFlavor(?string $flavor): void
    {
        $this->flavor = $flavor;
    }

    public function getRelatedArticles()
    {
        return $this->relatedArticles;
    }

    public function addRelatedArticle(Article $relatedArticle)
    {
        $this->relatedArticles->add($relatedArticle);
    }

    public function setRelatedArticles(?Collection $relatedArticles)
    {
        $this->relatedArticles = $relatedArticles ?? new ArrayCollection();
    }

    public function removeRelatedArticle(Article $relatedArticle)
    {
        $this->relatedArticles->removeElement($relatedArticle);
    }

    public function getSimilarArticles()
    {
        return $this->similarArticles;
    }

    public function addSimilarArticle(Article $similarArticle)
    {
        $this->similarArticles->add($similarArticle);
    }

    public function setSimilarArticles(?Collection $similarArticles)
    {
        $this->similarArticles = $similarArticles ?? new ArrayCollection();
    }

    public function removeSimilarArticle(Article $similarArticle)
    {
        $this->similarArticles->removeElement($similarArticle);
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
     * @param string|null $dosage
     */
    public function setDosage(?string $dosage)
    {
        $this->dosage = $dosage;
    }

    /**
     * @return string|null
     */
    public function getDosage() : ?string
    {
        return $this->dosage;
    }

    /**
     * @return bool
     */
    public function getActivateRelatedArticles(): bool
    {
        return $this->activateRelatedArticles;
    }

    /**
     * @param bool $activateRelatedArticles
     */
    public function setActivateRelatedArticles(bool $activateRelatedArticles): void
    {
        $this->activateRelatedArticles = $activateRelatedArticles;
    }

    /**
     * @return string|null
     */
    public function getBase(): ?string
    {
        return $this->base;
    }

    /**
     * @param string|null $base
     */
    public function setBase(?string $base): void
    {
        $this->base = $base;
    }

    /**
     * @return bool
     */
    public function getActivateSimilarArticles(): bool
    {
        return $this->activateSimilarArticles;
    }

    /**
     * @param bool $activateSimilarArticles
     */
    public function setActivateSimilarArticles(bool $activateSimilarArticles): void
    {
        $this->activateSimilarArticles = $activateSimilarArticles;
    }

}