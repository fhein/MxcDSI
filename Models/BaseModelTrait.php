<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 21.01.2019
 * Time: 00:21
 */

namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
trait BaseModelTrait
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
     * @var DateTime $created
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created = null;

    /**
     * @var DateTime $updated
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated = null;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps() {
        /** @noinspection PhpUnhandledExceptionInspection */
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
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }
}