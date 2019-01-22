<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MxcDropshipInnocigs\Models\BaseModelTrait;
use Shopware\Components\Model\ModelEntity;


/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dsi_article_import")
 * @ORM\Entity(repositoryClass="ImportArticleRepository")
 */
class ImportArticle extends ModelEntity  {

    use BaseModelTrait;

    /**
     * @var string $name
     * @ORM\Column()
     */
    private $number;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="ImportVariant",
     *      mappedBy="master",
     *      cascade={"persist", "remove"}
     * )
     */
    private $variants;


    public function __construct() {
        $this->variants = new ArrayCollection();
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

    // This API gets implicitly called when the user saves an article.

    //
    // If the 'Save' button gets clicked on the article detail window
    // updated variant information is provided (accepted status of each variant).
    //
    // If the 'Save' action gets triggered via the article listing
    // (cell editing, 'Activate selected', etc), an empty variant array
    // is provided.
    //
    // So we apply the variant array only if it is not empty. Otherwise
    // the variants, which are all well defined and present, would be removed.
    //
    public function setVariants($variants) {
        if (! empty($variants)) {
            $this->setOneToMany($variants, 'MxcDropshipInnocigs\Models\Import\ImportVariant', 'variants');
        }
    }
    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    /**
     * @param ImportVariant $variant
     */
    public function addVariant(ImportVariant $variant) {
        $this->variants->add($variant);
        $variant->setMaster($this);
    }
}