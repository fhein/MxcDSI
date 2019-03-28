<?php

namespace MxcDropshipInnocigs\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use ReflectionClass;
use ReflectionProperty;

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
    protected $id;

    /**
     * @var DateTime $created
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $created = null;

    /**
     * @var DateTime $updated
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated = null;

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
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    public function getPrivatePropertyNames(): array
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $r = new ReflectionClass($this);
        $properties = $r->getProperties(ReflectionProperty::IS_PRIVATE);
        $names = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $names[] = $name;
        }
        return $names;
    }
}