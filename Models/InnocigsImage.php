<?php /** @noinspection PhpUnhandledExceptionInspection */
namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_plugin_mxc_dropship_innocigs_image")
 */
class InnocigsImage extends ModelEntity
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
     * @var InnocigsVariant $variant
     * @ORM\ManyToOne(targetEntity="InnocigsVariant", inversedBy="images")
     */
    private $variant;

    /**
     * @var string $image
     *
     * @ORM\Column()
     */
    private $image;

    /**
     * @var DateTime $created
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created = null;

    /**
     * @var DateTime $updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated = null;

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
     * @return InnocigsVariant
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * @param InnocigsVariant $variant
     */
    public function setVariant(InnocigsVariant $variant)
    {
        $this->variant = $variant;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage(string $image)
    {
        $this->image = $image;
    }
}